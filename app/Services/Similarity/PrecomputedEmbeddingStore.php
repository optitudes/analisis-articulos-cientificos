<?php

namespace App\Services\Similarity;

use App\Services\Article\DTO\PreprocessedAbstractDTO;
use Illuminate\Support\Facades\File;
use JsonException;
use RuntimeException;

/** Reads compact, precomputed document vectors without loading source models at runtime. */
class PrecomputedEmbeddingStore
{
    /** @var array<string, mixed>|null */
    private ?array $artifact = null;

    public function isAvailable(string $model): bool
    {
        if (! File::exists($this->artifactPath())) {
            return false;
        }

        try {
            $artifact = $this->loadArtifact();
        } catch (RuntimeException) {
            return false;
        }

        $modelData = $artifact['models'][$model] ?? null;

        return is_array($modelData)
            && isset($modelData['dimensions'])
            && (int) $modelData['dimensions'] > 0
            && isset($modelData['articles'])
            && is_array($modelData['articles']);
    }

    /** @param array<int, PreprocessedAbstractDTO> $abstracts
     * @return array{vectors: array<int, array<int, float>>, coverage: array<int, array{coveredTerms: int, totalTerms: int}>, dimensions: int}
     */
    public function forArticles(string $model, array $abstracts): array
    {
        $artifact = $this->loadArtifact();
        $modelData = $artifact['models'][$model] ?? null;

        if (! is_array($modelData) || ! isset($modelData['dimensions'], $modelData['articles'])) {
            throw new RuntimeException("No existen embeddings precomputados para el modelo '{$model}'.");
        }

        $dimensions = (int) $modelData['dimensions'];
        if ($dimensions < 1 || ! is_array($modelData['articles'])) {
            throw new RuntimeException("El artefacto de embeddings para '{$model}' no tiene un formato válido.");
        }

        $vectors = [];
        $coverage = [];

        foreach ($abstracts as $abstract) {
            $relativePath = $abstract->relativePath;
            $articleEmbedding = $modelData['articles'][$relativePath] ?? null;

            if (! is_array($articleEmbedding) || ! isset($articleEmbedding['vector'])) {
                throw new RuntimeException(
                    "Falta el embedding '{$model}' de '{$relativePath}'. Regenera el artefacto después del seeder.",
                );
            }

            $vector = $articleEmbedding['vector'];
            if (! is_array($vector) || count($vector) !== $dimensions) {
                throw new RuntimeException("El vector '{$model}' de '{$relativePath}' tiene dimensiones incorrectas.");
            }

            $numericVector = [];
            foreach ($vector as $value) {
                if (! is_numeric($value)) {
                    throw new RuntimeException("El vector '{$model}' de '{$relativePath}' contiene valores no numéricos.");
                }

                $numericVector[] = (float) $value;
            }

            $vectors[] = $numericVector;
            $coverage[] = [
                'coveredTerms' => (int) ($articleEmbedding['covered_terms'] ?? 0),
                'totalTerms' => (int) ($articleEmbedding['total_terms'] ?? 0),
            ];
        }

        return [
            'vectors' => $vectors,
            'coverage' => $coverage,
            'dimensions' => $dimensions,
        ];
    }

    private function artifactPath(): string
    {
        return (string) config('embeddings.artifact_path', public_path('embeddings/article-vectors.json'));
    }

    /** @return array<string, mixed> */
    private function loadArtifact(): array
    {
        if ($this->artifact !== null) {
            return $this->artifact;
        }

        $path = $this->artifactPath();
        if (! File::exists($path)) {
            throw new RuntimeException(
                "No se encontró {$path}. Ejecuta embeddings:export-corpus y scripts/generate_embeddings.py.",
            );
        }

        try {
            $artifact = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('El archivo de embeddings no contiene JSON válido.', previous: $exception);
        }

        if (! is_array($artifact) || ($artifact['schema_version'] ?? null) !== 1 || ! is_array($artifact['models'] ?? null)) {
            throw new RuntimeException('El archivo de embeddings tiene una versión o estructura no compatible.');
        }

        $this->artifact = $artifact;

        return $this->artifact;
    }
}
