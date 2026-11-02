import gzip
import struct
import tempfile
import unittest
from pathlib import Path

from generate_embeddings import (
    load_needed_text_vectors,
    load_needed_word2vec_binary,
    vectorize_corpus,
)


class GenerateEmbeddingsTest(unittest.TestCase):
    def test_text_vectors_are_streamed_and_only_needed_words_are_used(self):
        with tempfile.TemporaryDirectory() as directory:
            model_path = Path(directory) / "small.vec"
            model_path.write_text(
                "4 2\n"
                "ai 1 0\n"
                "education 0 1\n"
                "unused 9 9\n"
                "research 1 1\n",
                encoding="utf-8",
            )

            vectors, dimensions = load_needed_text_vectors(model_path, {"ai", "education"})

        self.assertEqual(2, dimensions)
        self.assertEqual({"ai", "education"}, set(vectors))

    def test_gzip_text_vectors_are_supported(self):
        with tempfile.TemporaryDirectory() as directory:
            model_path = Path(directory) / "small.vec.gz"
            with gzip.open(model_path, "wt", encoding="utf-8") as model_file:
                model_file.write("2 2\nai 1 0\neducation 0 1\n")

            vectors, dimensions = load_needed_text_vectors(model_path, {"ai"})

        self.assertEqual(2, dimensions)
        self.assertEqual([1.0, 0.0], vectors["ai"])

    def test_word2vec_binary_is_read_without_loading_full_vocabulary(self):
        with tempfile.TemporaryDirectory() as directory:
            model_path = Path(directory) / "small.bin"
            with model_path.open("wb") as model_file:
                model_file.write(b"2 2\n")
                model_file.write(b"ai ")
                model_file.write(struct.pack("<2f", 1.0, 0.0))
                model_file.write(b"\n")
                model_file.write(b"research ")
                model_file.write(struct.pack("<2f", 0.0, 1.0))
                model_file.write(b"\n")

            vectors, dimensions = load_needed_word2vec_binary(model_path, {"ai"})

        self.assertEqual(2, dimensions)
        self.assertEqual([1.0, 0.0], vectors["ai"])

    def test_document_vectors_average_known_words_and_report_coverage(self):
        with tempfile.TemporaryDirectory() as directory:
            model_path = Path(directory) / "small.vec"
            model_path.write_text("2 2\nai 1 0\neducation 0 1\n", encoding="utf-8")
            corpus = {
                "articles": [
                    {
                        "relative_path": "articles/a.pdf",
                        "terms": ["ai", "education", "missing"],
                    }
                ]
            }

            result = vectorize_corpus(corpus, model_path, "word2vec")

        article = result["articles"]["articles/a.pdf"]
        self.assertEqual([0.5, 0.5], article["vector"])
        self.assertEqual(2, article["covered_terms"])
        self.assertEqual(3, article["total_terms"])
        self.assertEqual(2, result["dimensions"])


if __name__ == "__main__":
    unittest.main()
