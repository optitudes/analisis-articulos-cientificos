<?php

namespace Tests\Unit;

use App\Services\Clustering\AverageLinkageClusteringService;
use App\Services\Clustering\CompleteLinkageClusteringService;
use App\Services\Clustering\SilhouetteScoreService;
use App\Services\Clustering\SingleLinkageClusteringService;
use App\Services\Similarity\EuclideanDistanceMatrixBuilder;
use PHPUnit\Framework\TestCase;

class HierarchicalClusteringTest extends TestCase
{
    public function test_each_linkage_applies_its_own_cluster_distance_rule(): void
    {
        $distanceMatrix = (new EuclideanDistanceMatrixBuilder)->build([
            [0.0],
            [4.0],
            [5.0],
            [10.0],
        ]);

        $singleMerges = (new SingleLinkageClusteringService)->cluster($distanceMatrix);
        $completeMerges = (new CompleteLinkageClusteringService)->cluster($distanceMatrix);
        $averageMerges = (new AverageLinkageClusteringService)->cluster($distanceMatrix);

        $this->assertCount(3, $singleMerges);
        $this->assertCount(3, $completeMerges);
        $this->assertCount(3, $averageMerges);

        // The first fusion is the closest pair (4, 5); the next distance differs by linkage.
        $this->assertSame(1.0, $singleMerges[0]['distance']);
        $this->assertSame(1.0, $completeMerges[0]['distance']);
        $this->assertSame(1.0, $averageMerges[0]['distance']);
        $this->assertSame(4.0, $singleMerges[1]['distance']);
        $this->assertSame(5.0, $completeMerges[1]['distance']);
        $this->assertSame(4.5, $averageMerges[1]['distance']);
    }

    public function test_silhouette_selects_the_best_cut_and_returns_assignments(): void
    {
        $distanceMatrix = (new EuclideanDistanceMatrixBuilder)->build([
            [0.0],
            [0.5],
            [10.0],
            [10.5],
        ]);
        $merges = (new AverageLinkageClusteringService)->cluster($distanceMatrix);

        $evaluation = (new SilhouetteScoreService)->evaluateCuts($merges, $distanceMatrix);

        $this->assertSame(2, $evaluation['bestClusterCount']);
        $this->assertGreaterThan(0.9, $evaluation['bestScore']);
        $this->assertCount(4, $evaluation['clusterAssignments']);
        $this->assertCount(2, $evaluation['scoresByClusterCount']);
        $this->assertCount(2, array_unique($evaluation['clusterAssignments']));
    }
}
