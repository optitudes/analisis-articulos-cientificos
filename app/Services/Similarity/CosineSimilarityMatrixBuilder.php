<?php

namespace App\Services\Similarity;

/** Calculates a symmetric cosine-similarity matrix from dense numeric vectors. */
class CosineSimilarityMatrixBuilder
{
    /** @param array<int, array<int, float>> $vectors
     * @return array<int, array<int, float>>
     */
    public function build(array $vectors): array
    {
        $vectorCount = count($vectors);
        $vectorLengths = [];

        // Calculate each vector length once before comparing article pairs.
        for ($index = 0; $index < $vectorCount; $index++) {
            $squaredLength = 0.0;
            foreach ($vectors[$index] as $value) {
                $squaredLength += $value * $value;
            }
            $vectorLengths[$index] = sqrt($squaredLength);
        }

        $matrix = [];
        for ($row = 0; $row < $vectorCount; $row++) {
            if (! isset($matrix[$row])) {
                $matrix[$row] = [];
            }

            for ($column = $row; $column < $vectorCount; $column++) {
                $dotProduct = 0.0;
                $dimensions = count($vectors[$row]);

                for ($dimension = 0; $dimension < $dimensions; $dimension++) {
                    $dotProduct += $vectors[$row][$dimension] * $vectors[$column][$dimension];
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
