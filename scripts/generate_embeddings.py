#!/usr/bin/env python3
"""Generate compact article vectors from pretrained text-format word vectors.

The model files are scanned line by line. Only terms present in the exported
seed corpus are retained in memory, so this script does not load full models.
It uses only the Python standard library.
"""

from __future__ import annotations

import argparse
import contextlib
import gzip
import io
import json
import os
import struct
import tempfile
import unicodedata
import zipfile
from pathlib import Path
from typing import BinaryIO, Iterator, TextIO


def normalize_model_term(term: str) -> str:
    """Normalize a model vocabulary item like ArticleService tokenizes terms."""
    decomposed = unicodedata.normalize("NFKD", term)
    ascii_term = decomposed.encode("ascii", "ignore").decode("ascii")
    return ascii_term.lower()


@contextlib.contextmanager
def open_text_vector_file(path: Path) -> Iterator[TextIO]:
    """Open plain, gzip-compressed, or zip-compressed .vec text files."""
    if path.suffix.lower() == ".zip":
        with zipfile.ZipFile(path) as archive:
            vector_files = [name for name in archive.namelist() if name.lower().endswith(".vec")]
            if not vector_files:
                raise ValueError(f"El ZIP no contiene un archivo .vec: {path}")

            with archive.open(vector_files[0]) as compressed_file:
                with io.TextIOWrapper(compressed_file, encoding="utf-8", errors="replace") as text_file:
                    yield text_file
        return

    if path.suffix.lower() == ".gz":
        with gzip.open(path, mode="rt", encoding="utf-8", errors="replace") as text_file:
            yield text_file
        return

    with path.open(mode="r", encoding="utf-8", errors="replace") as text_file:
        yield text_file


@contextlib.contextmanager
def open_binary_vector_file(path: Path) -> Iterator[BinaryIO]:
    """Open a Word2Vec binary model, optionally compressed with gzip."""
    if path.suffix.lower() == ".gz":
        with gzip.open(path, mode="rb") as binary_file:
            yield binary_file
        return

    with path.open(mode="rb") as binary_file:
        yield binary_file


def parse_header(line: str) -> tuple[int, int] | None:
    """Return vocabulary size and dimensions for a standard two-number header."""
    parts = line.split()
    if len(parts) != 2:
        return None

    try:
        vocabulary_size = int(parts[0])
        dimensions = int(parts[1])
    except ValueError:
        return None

    if vocabulary_size < 0 or dimensions < 1:
        return None

    return vocabulary_size, dimensions


def load_needed_text_vectors(model_path: Path, needed_terms: set[str]) -> tuple[dict[str, list[float]], int]:
    """Stream a .vec file and retain only vectors requested by the corpus."""
    vectors: dict[str, list[float]] = {}
    dimensions = 0

    with open_text_vector_file(model_path) as vector_file:
        first_line = vector_file.readline()
        header = parse_header(first_line)

        if header is not None:
            dimensions = header[1]
        else:
            vector_file.seek(0)

        for line in vector_file:
            fields = line.rstrip().split()
            if len(fields) < 2:
                continue

            term = normalize_model_term(fields[0])
            if term not in needed_terms or term in vectors:
                continue

            if dimensions == 0:
                dimensions = len(fields) - 1

            if len(fields) - 1 != dimensions:
                continue

            try:
                vector = [float(value) for value in fields[1:]]
            except ValueError:
                continue

            vectors[term] = vector

            # Stop reading once every term in this fixed corpus has been found.
            if len(vectors) == len(needed_terms):
                break

    if dimensions == 0:
        raise ValueError(f"No se pudo determinar la dimensión del modelo: {model_path}")

    if not vectors:
        raise ValueError(f"El modelo no contiene términos del corpus: {model_path}")

    return vectors, dimensions


def read_binary_token(binary_file: BinaryIO) -> str | None:
    """Read one space-terminated token from the Word2Vec binary format."""
    token = bytearray()

    while True:
        character = binary_file.read(1)
        if not character:
            return token.decode("utf-8", errors="replace") if token else None

        if character == b" ":
            if token:
                return token.decode("utf-8", errors="replace")
            continue

        if character in (b"\n", b"\r") and not token:
            continue

        token.extend(character)


def load_needed_word2vec_binary(
    model_path: Path, needed_terms: set[str]
) -> tuple[dict[str, list[float]], int]:
    """Stream a standard Word2Vec binary model without unpacking its full vocabulary."""
    vectors: dict[str, list[float]] = {}

    with open_binary_vector_file(model_path) as binary_file:
        header = binary_file.readline().split()
        if len(header) != 2:
            raise ValueError(f"El encabezado del modelo Word2Vec no es válido: {model_path}")

        try:
            vocabulary_size = int(header[0])
            dimensions = int(header[1])
        except ValueError as exception:
            raise ValueError(f"El encabezado del modelo Word2Vec no es válido: {model_path}") from exception

        if vocabulary_size < 1 or dimensions < 1:
            raise ValueError(f"El encabezado del modelo Word2Vec no es válido: {model_path}")

        vector_byte_count = dimensions * 4
        for _ in range(vocabulary_size):
            raw_term = read_binary_token(binary_file)
            if raw_term is None:
                break

            term = normalize_model_term(raw_term)
            raw_vector = binary_file.read(vector_byte_count)
            if len(raw_vector) != vector_byte_count:
                raise ValueError(f"El modelo Word2Vec está incompleto: {model_path}")

            if term in needed_terms and term not in vectors:
                vectors[term] = list(struct.unpack(f"<{dimensions}f", raw_vector))

            if len(vectors) == len(needed_terms):
                break

    if not vectors:
        raise ValueError(f"El modelo no contiene términos del corpus: {model_path}")

    return vectors, dimensions


def mean_document_vector(terms: list[str], vectors: dict[str, list[float]], dimensions: int) -> dict:
    """Average available word vectors and report vocabulary coverage."""
    totals = [0.0] * dimensions
    covered_terms = 0

    for term in terms:
        term_vector = vectors.get(term)
        if term_vector is None:
            continue

        covered_terms += 1
        for index in range(dimensions):
            totals[index] += term_vector[index]

    if covered_terms > 0:
        for index in range(dimensions):
            totals[index] /= covered_terms

    return {
        "vector": totals,
        "covered_terms": covered_terms,
        "total_terms": len(terms),
    }


def vectorize_corpus(corpus: dict, model_path: Path, model_key: str) -> dict:
    """Vectorize every article, releasing each source vocabulary before next model."""
    articles = corpus.get("articles")
    if not isinstance(articles, list) or not articles:
        raise ValueError("El JSON de entrada debe contener una lista 'articles' no vacía.")

    needed_terms = {
        term
        for article in articles
        for term in article.get("terms", [])
        if isinstance(term, str) and term
    }
    if not needed_terms:
        raise ValueError("El corpus no contiene términos procesables.")

    if model_key == "word2vec" and model_path.name.lower().endswith((".bin", ".bin.gz")):
        model_vectors, dimensions = load_needed_word2vec_binary(model_path, needed_terms)
    else:
        model_vectors, dimensions = load_needed_text_vectors(model_path, needed_terms)
    article_vectors = {}

    for article in articles:
        relative_path = article.get("relative_path")
        if not relative_path:
            raise ValueError("Cada artículo debe tener 'relative_path' como clave estable.")

        article_vectors[relative_path] = mean_document_vector(
            article.get("terms", []), model_vectors, dimensions
        )

    covered_vocabulary = len(model_vectors)
    del model_vectors

    return {
        "source_file": model_path.name,
        "dimensions": dimensions,
        "covered_vocabulary_terms": covered_vocabulary,
        "requested_vocabulary_terms": len(needed_terms),
        "articles": article_vectors,
    }


def write_json_atomically(output_path: Path, contents: dict) -> None:
    """Write the generated vectors without leaving a partial artifact on failure."""
    output_path.parent.mkdir(parents=True, exist_ok=True)
    temporary_path = None

    try:
        with tempfile.NamedTemporaryFile(
            mode="w",
            encoding="utf-8",
            dir=output_path.parent,
            prefix=f"{output_path.name}.",
            suffix=".tmp",
            delete=False,
        ) as temporary_file:
            temporary_path = Path(temporary_file.name)
            json.dump(contents, temporary_file, ensure_ascii=False, separators=(",", ":"))
            temporary_file.write("\n")

        # The artifact is copied into the application image and must be readable by www-data.
        os.chmod(temporary_path, 0o644)
        os.replace(temporary_path, output_path)
    finally:
        if temporary_path is not None and temporary_path.exists():
            temporary_path.unlink()


def parse_arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Genera embeddings compactos leyendo modelos Word2Vec/FastText en streaming."
    )
    parser.add_argument("--input", required=True, type=Path, help="JSON generado por embeddings:export-corpus")
    parser.add_argument("--output", required=True, type=Path, help="JSON compacto de salida")
    parser.add_argument(
        "--word2vec",
        required=True,
        type=Path,
        help="Modelo Word2Vec (.bin[.gz], .vec, .vec.gz o ZIP con .vec)",
    )
    parser.add_argument(
        "--fasttext",
        required=True,
        type=Path,
        help="Modelo FastText de texto (.vec, .vec.gz o ZIP con .vec)",
    )
    return parser.parse_args()


def main() -> int:
    arguments = parse_arguments()

    try:
        with arguments.input.open(mode="r", encoding="utf-8") as corpus_file:
            corpus = json.load(corpus_file)

        # Process one source model at a time to keep peak memory low.
        word2vec = vectorize_corpus(corpus, arguments.word2vec, "word2vec")
        fasttext = vectorize_corpus(corpus, arguments.fasttext, "fasttext")

        output = {
            "schema_version": 1,
            "models": {
                "word2vec": word2vec,
                "fasttext": fasttext,
            },
        }
        write_json_atomically(arguments.output, output)
    except (OSError, json.JSONDecodeError, struct.error, ValueError) as exception:
        print(f"Error al generar embeddings: {exception}")
        return 1

    print(f"Embeddings generados en: {arguments.output}")
    for model_name, model in output["models"].items():
        print(
            f"{model_name}: {model['dimensions']} dimensiones, "
            f"vocabulario cubierto {model['covered_vocabulary_terms']}/"
            f"{model['requested_vocabulary_terms']}"
        )

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
