<?php

namespace App\Services\Clustering;

use InvalidArgumentException;
use RuntimeException;

/** Comparte la construcción del dendrograma; cada servicio define su regla de enlace. */
abstract class AbstractHierarchicalClusteringService
{
    /**
     * Construye el árbol aglomerativo a partir de una matriz simétrica de distancias.
     *
     * Los nodos hoja usan los índices 0..n-1 y cada nodo de fusión recibe un ID nuevo.
     *
     * @param  array<int, array<int, int|float>>  $distanceMatrix
     * @return array<int, array{id: int, left: int, right: int, distance: float, size: int}>
     */
    public function cluster(array $distanceMatrix): array
    {
        $articleCount = count($distanceMatrix);
        $this->validateDistanceMatrix($distanceMatrix, $articleCount);

        $activeClusters = [];
        for ($articleIndex = 0; $articleIndex < $articleCount; $articleIndex++) {
            $activeClusters[$articleIndex] = [$articleIndex];
        }

        $merges = [];

        while (count($activeClusters) > 1) {
            $clusterIds = array_keys($activeClusters);
            $bestLeftId = null;
            $bestRightId = null;
            $bestDistance = INF;

            // Scan all active pairs; loop order makes equal-distance choices deterministic.
            for ($leftIndex = 0; $leftIndex < count($clusterIds) - 1; $leftIndex++) {
                for ($rightIndex = $leftIndex + 1; $rightIndex < count($clusterIds); $rightIndex++) {
                    $leftId = $clusterIds[$leftIndex];
                    $rightId = $clusterIds[$rightIndex];
                    $distance = $this->linkageDistance(
                        $activeClusters[$leftId],
                        $activeClusters[$rightId],
                        $distanceMatrix,
                    );

                    if ($distance < $bestDistance) {
                        $bestDistance = $distance;
                        $bestLeftId = $leftId;
                        $bestRightId = $rightId;
                    }
                }
            }

            if ($bestLeftId === null || $bestRightId === null) {
                throw new RuntimeException('No se encontró una pareja de clusters para fusionar.');
            }

            $leftMembers = $activeClusters[$bestLeftId];
            $rightMembers = $activeClusters[$bestRightId];
            $mergedMembers = array_merge($leftMembers, $rightMembers);
            $newNodeId = $articleCount + count($merges);

            $merges[] = [
                'id' => $newNodeId,
                'left' => $bestLeftId,
                'right' => $bestRightId,
                'distance' => (float) $bestDistance,
                'size' => count($mergedMembers),
            ];

            unset($activeClusters[$bestLeftId], $activeClusters[$bestRightId]);
            $activeClusters[$newNodeId] = $mergedMembers;
        }

        return $merges;
    }

    /**
     * Calcula la distancia entre los miembros de dos clusters según el linkage concreto.
     *
     * @param  array<int, int>  $leftMembers
     * @param  array<int, int>  $rightMembers
     * @param  array<int, array<int, int|float>>  $distanceMatrix
     */
    abstract protected function linkageDistance(array $leftMembers, array $rightMembers, array $distanceMatrix): float;

    /** @param array<int, array<int, int|float>> $distanceMatrix */
    private function validateDistanceMatrix(array $distanceMatrix, int $articleCount): void
    {
        if ($articleCount < 2) {
            throw new InvalidArgumentException('El agrupamiento jerárquico requiere al menos dos artículos.');
        }

        for ($row = 0; $row < $articleCount; $row++) {
            if (! isset($distanceMatrix[$row]) || count($distanceMatrix[$row]) !== $articleCount) {
                throw new InvalidArgumentException('La matriz de distancias debe ser cuadrada.');
            }
        }

        for ($row = 0; $row < $articleCount; $row++) {
            for ($column = 0; $column < $articleCount; $column++) {
                $distance = $distanceMatrix[$row][$column] ?? null;
                if (! is_numeric($distance) || $distance < 0) {
                    throw new InvalidArgumentException('Las distancias deben ser valores numéricos no negativos.');
                }

                if ($row === $column && abs($distance) > 1.0e-9) {
                    throw new InvalidArgumentException('La diagonal de la matriz de distancias debe ser cero.');
                }

                if (abs($distance - $distanceMatrix[$column][$row]) > 1.0e-9) {
                    throw new InvalidArgumentException('La matriz de distancias debe ser simétrica.');
                }
            }
        }
    }
}
