<?php

namespace App\Services\Clustering;

/** Fusiona dos clusters según la mayor distancia entre cualquier pareja de sus puntos. */
class CompleteLinkageClusteringService extends AbstractHierarchicalClusteringService
{
    /**
     * @param  array<int, int>  $leftMembers
     * @param  array<int, int>  $rightMembers
     * @param  array<int, array<int, int|float>>  $distanceMatrix
     */
    protected function linkageDistance(array $leftMembers, array $rightMembers, array $distanceMatrix): float
    {
        $maximumDistance = 0.0;

        // Complete Linkage evita unir grupos si existe una pareja muy distante.
        foreach ($leftMembers as $leftArticle) {
            foreach ($rightMembers as $rightArticle) {
                $distance = (float) $distanceMatrix[$leftArticle][$rightArticle];
                if ($distance > $maximumDistance) {
                    $maximumDistance = $distance;
                }
            }
        }

        return $maximumDistance;
    }
}
