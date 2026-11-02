<?php

namespace App\Services\Clustering;

/** Fusiona dos clusters según la menor distancia entre cualquier pareja de sus puntos. */
class SingleLinkageClusteringService extends AbstractHierarchicalClusteringService
{
    /**
     * @param  array<int, int>  $leftMembers
     * @param  array<int, int>  $rightMembers
     * @param  array<int, array<int, int|float>>  $distanceMatrix
     */
    protected function linkageDistance(array $leftMembers, array $rightMembers, array $distanceMatrix): float
    {
        $minimumDistance = INF;

        // Single Linkage usa el vecino más cercano entre los dos grupos.
        foreach ($leftMembers as $leftArticle) {
            foreach ($rightMembers as $rightArticle) {
                $distance = (float) $distanceMatrix[$leftArticle][$rightArticle];
                if ($distance < $minimumDistance) {
                    $minimumDistance = $distance;
                }
            }
        }

        return $minimumDistance;
    }
}
