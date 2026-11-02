<?php

namespace App\Services\Similarity;

/** Construye una matriz simétrica de distancias euclidianas entre vectores densos. */
class EuclideanDistanceMatrixBuilder
{
    /** @param array<int, array<int, float>> $vectors
     * @return array<int, array<int, float>>
     */
    public function build(array $vectors): array
    {
        $vectorCount = count($vectors);
        $matrix = [];

        // Compara cada pareja una vez y copia la distancia en ambos sentidos.
        for ($row = 0; $row < $vectorCount; $row++) {
            if (! isset($matrix[$row])) {
                $matrix[$row] = [];
            }

            for ($column = $row; $column < $vectorCount; $column++) {
                $squaredDistance = 0.0;
                $dimensions = count($vectors[$row]);

                for ($dimension = 0; $dimension < $dimensions; $dimension++) {
                    $difference = $vectors[$row][$dimension] - $vectors[$column][$dimension];
                    $squaredDistance += $difference * $difference;
                }

                $distance = sqrt($squaredDistance);
                $matrix[$row][$column] = $distance;
                $matrix[$column][$row] = $distance;
            }
        }

        return $matrix;
    }
}
