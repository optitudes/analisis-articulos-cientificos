<?php

namespace App\Services\Clustering;

/** Fusiona dos clusters usando el promedio de todas las distancias entre sus puntos. */
class AverageLinkageClusteringService extends AbstractHierarchicalClusteringService
{
    /**
     * @param  array<int, int>  $leftMembers
     * @param  array<int, int>  $rightMembers
     * @param  array<int, array<int, int|float>>  $distanceMatrix
     */
    protected function linkageDistance(array $leftMembers, array $rightMembers, array $distanceMatrix): float
    {
        $distanceTotal = 0.0;
        $articlePairCount = 0;

        // Average Linkage considera todas las parejas y no solo los extremos.
        foreach ($leftMembers as $leftArticle) {
            foreach ($rightMembers as $rightArticle) {
                $distanceTotal += $distanceMatrix[$leftArticle][$rightArticle];
                $articlePairCount++;
            }
        }

        return $distanceTotal / $articlePairCount;
    }
}
