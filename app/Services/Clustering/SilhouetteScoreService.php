<?php

namespace App\Services\Clustering;

use InvalidArgumentException;

/** Evalúa cada corte posible del dendrograma y conserva el mejor score silhouette. */
class SilhouetteScoreService
{
    /**
     * Prueba los cortes con k=2..n-1 clusters y elige el score promedio más alto.
     *
     * @param  array<int, array{id: int, left: int, right: int, distance: float, size: int}>  $merges
     * @param  array<int, array<int, int|float>>  $distanceMatrix
     * @return array{bestClusterCount: int, bestScore: float, scoresByClusterCount: array<int, array{clusterCount: int, score: float}>, clusterAssignments: array<int, int>}
     */
    public function evaluateCuts(array $merges, array $distanceMatrix): array
    {
        $articleCount = count($distanceMatrix);
        if ($articleCount < 3 || count($merges) !== $articleCount - 1) {
            throw new InvalidArgumentException('Silhouette requiere un dendrograma completo de al menos tres artículos.');
        }

        $activeClusters = [];
        $clusterAssignments = [];
        for ($articleIndex = 0; $articleIndex < $articleCount; $articleIndex++) {
            $activeClusters[$articleIndex] = [$articleIndex];
            $clusterAssignments[$articleIndex] = $articleIndex;
        }

        $scoresByClusterCount = [];
        $bestClusterCount = 0;
        $bestScore = -INF;
        $bestAssignments = [];

        foreach ($merges as $merge) {
            $leftMembers = $activeClusters[$merge['left']] ?? null;
            $rightMembers = $activeClusters[$merge['right']] ?? null;
            if ($leftMembers === null || $rightMembers === null) {
                throw new InvalidArgumentException('El dendrograma contiene referencias de fusión inválidas.');
            }

            $mergedMembers = array_merge($leftMembers, $rightMembers);
            unset($activeClusters[$merge['left']], $activeClusters[$merge['right']]);
            $activeClusters[$merge['id']] = $mergedMembers;

            foreach ($mergedMembers as $articleIndex) {
                $clusterAssignments[$articleIndex] = $merge['id'];
            }

            $clusterCount = count($activeClusters);
            if ($clusterCount < 2 || $clusterCount >= $articleCount) {
                continue;
            }

            $score = $this->averageSilhouette($activeClusters, $distanceMatrix, $articleCount);
            $scoresByClusterCount[] = ['clusterCount' => $clusterCount, 'score' => $score];

            // Resolve score ties in favor of the simpler cut with fewer clusters.
            $isBetterScore = $score > $bestScore + 1.0e-12;
            $isSimplerTie = abs($score - $bestScore) <= 1.0e-12
                && ($bestClusterCount === 0 || $clusterCount < $bestClusterCount);

            if ($isBetterScore || $isSimplerTie) {
                $bestScore = $score;
                $bestClusterCount = $clusterCount;
                $bestAssignments = $clusterAssignments;
            }
        }

        return [
            'bestClusterCount' => $bestClusterCount,
            'bestScore' => (float) $bestScore,
            'scoresByClusterCount' => $scoresByClusterCount,
            'clusterAssignments' => $bestAssignments,
        ];
    }

    /**
     * Calcula el promedio silhouette de todos los abstracts en un corte dado.
     *
     * @param  array<int, array<int, int>>  $clusters
     * @param  array<int, array<int, int|float>>  $distanceMatrix
     */
    private function averageSilhouette(array $clusters, array $distanceMatrix, int $articleCount): float
    {
        $silhouetteTotal = 0.0;

        foreach ($clusters as $clusterId => $members) {
            foreach ($members as $articleIndex) {
                // El silhouette de un grupo singleton se define como cero.
                if (count($members) === 1) {
                    continue;
                }

                $withinClusterDistance = 0.0;
                foreach ($members as $otherArticle) {
                    if ($otherArticle !== $articleIndex) {
                        $withinClusterDistance += $distanceMatrix[$articleIndex][$otherArticle];
                    }
                }
                $averageWithinDistance = $withinClusterDistance / (count($members) - 1);

                $nearestOtherClusterDistance = INF;
                foreach ($clusters as $otherClusterId => $otherMembers) {
                    if ($otherClusterId === $clusterId) {
                        continue;
                    }

                    $distanceTotal = 0.0;
                    foreach ($otherMembers as $otherArticle) {
                        $distanceTotal += $distanceMatrix[$articleIndex][$otherArticle];
                    }

                    $averageOtherDistance = $distanceTotal / count($otherMembers);
                    if ($averageOtherDistance < $nearestOtherClusterDistance) {
                        $nearestOtherClusterDistance = $averageOtherDistance;
                    }
                }

                $normalizer = max($averageWithinDistance, $nearestOtherClusterDistance);
                if ($normalizer > 0.0) {
                    $silhouetteTotal += (
                        $nearestOtherClusterDistance - $averageWithinDistance
                    ) / $normalizer;
                }
            }
        }

        return $silhouetteTotal / $articleCount;
    }
}
