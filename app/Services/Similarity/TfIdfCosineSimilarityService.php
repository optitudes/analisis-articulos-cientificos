<?php

namespace App\Services\Similarity;

use App\Services\Article\DTO\PreprocessedAbstractDTO;

/** Construye una matriz de similitudes coseno usando vectores TF-IDF suavizados. */
class TfIdfCosineSimilarityService
{
    /** @param array<int, PreprocessedAbstractDTO> $abstracts
     * @return array<int, array<int, float>>
     */
    public function compare(array $abstracts): array
    {
        $documentCount = count($abstracts);
        $termFrequencies = [];
        $documentFrequencies = [];

        // Cuenta las apariciones y registra cada término una sola vez por abstract para calcular df.
        foreach ($abstracts as $documentIndex => $abstract) {
            $termFrequencies[$documentIndex] = [];
            $termsSeenInDocument = [];

            foreach ($abstract->terms as $term) {
                $termFrequencies[$documentIndex][$term] = ($termFrequencies[$documentIndex][$term] ?? 0) + 1;

                if (! isset($termsSeenInDocument[$term])) {
                    $termsSeenInDocument[$term] = true;
                    $documentFrequencies[$term] = ($documentFrequencies[$term] ?? 0) + 1;
                }
            }
        }

        $vectors = [];
        $vectorLengths = [];

        // Construye vectores dispersos ponderados y guarda la longitud euclidiana de cada vector.
        foreach ($termFrequencies as $documentIndex => $frequencies) {
            $vectors[$documentIndex] = [];
            $squaredLength = 0.0;

            foreach ($frequencies as $term => $frequency) {
                $inverseDocumentFrequency = log(($documentCount + 1) / ($documentFrequencies[$term] + 1)) + 1;
                $weight = $frequency * $inverseDocumentFrequency;
                $vectors[$documentIndex][$term] = $weight;
                $squaredLength += $weight * $weight;
            }

            $vectorLengths[$documentIndex] = sqrt($squaredLength);
        }

        $matrix = [];

        for ($row = 0; $row < $documentCount; $row++) {
            if (! isset($matrix[$row])) {
                $matrix[$row] = [];
            }

            for ($column = $row; $column < $documentCount; $column++) {
                $dotProduct = 0.0;

                foreach ($vectors[$row] as $term => $weight) {
                    if (isset($vectors[$column][$term])) {
                        $dotProduct += $weight * $vectors[$column][$term];
                    }
                }

                $denominator = $vectorLengths[$row] * $vectorLengths[$column];
                $similarity = $denominator === 0.0 ? 0.0 : $dotProduct / $denominator;

                $matrix[$row][$column] = $similarity;
                $matrix[$column][$row] = $similarity;
            }
        }

        return $matrix;
    }
}
