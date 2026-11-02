<?php

namespace App\Http\Controllers\HierarchicalClustering;

use App\Http\Controllers\Controller;
use App\Services\Article\ArticleService;
use App\Services\Clustering\AverageLinkageClusteringService;
use App\Services\Clustering\CompleteLinkageClusteringService;
use App\Services\Clustering\SilhouetteScoreService;
use App\Services\Clustering\SingleLinkageClusteringService;
use App\Services\Similarity\EuclideanDistanceMatrixBuilder;
use App\Services\Similarity\PrecomputedEmbeddingStore;
use Inertia\Inertia;
use Inertia\Response;

/** Orquesta los tres linkages y compara sus cortes mediante silhouette. */
class HierarchicalClusteringController extends Controller
{
    public function __construct(
        private readonly ArticleService $articles,
        private readonly PrecomputedEmbeddingStore $embeddingStore,
        private readonly EuclideanDistanceMatrixBuilder $distanceBuilder,
        private readonly SingleLinkageClusteringService $singleLinkage,
        private readonly CompleteLinkageClusteringService $completeLinkage,
        private readonly AverageLinkageClusteringService $averageLinkage,
        private readonly SilhouetteScoreService $silhouette,
    ) {}

    public function index(): Response
    {
        return Inertia::render('hierarchical-clustering/index', [
            'articles' => $this->articles->rankingArticles(),
            'embeddingAvailable' => $this->embeddingStore->isAvailable('fasttext'),
        ]);
    }

    public function results(): Response
    {
        abort_unless(
            $this->embeddingStore->isAvailable('fasttext'),
            503,
            'Genera los embeddings FastText antes de calcular el agrupamiento jerárquico.',
        );

        $selectedArticles = $this->articles->rankingArticles();
        $articleIds = $selectedArticles->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        abort_if(count($articleIds) < 3, 422, 'Se requieren al menos tres artículos para evaluar silhouette.');

        $abstracts = $this->articles->preprocessAbstracts($articleIds);
        $embeddingData = $this->embeddingStore->forArticles('fasttext', $abstracts);
        $distanceMatrix = $this->distanceBuilder->build($embeddingData['vectors']);

        $linkages = [
            'single-linkage' => [
                'label' => 'Single Linkage',
                'service' => $this->singleLinkage,
            ],
            'complete-linkage' => [
                'label' => 'Complete Linkage',
                'service' => $this->completeLinkage,
            ],
            'average-linkage' => [
                'label' => 'Average Linkage',
                'service' => $this->averageLinkage,
            ],
        ];

        $results = [];
        $highestSilhouette = -INF;

        foreach ($linkages as $algorithm => $linkage) {
            // El cronómetro incluye la construcción del árbol y la evaluación de sus cortes.
            $startedAt = hrtime(true);
            $merges = $linkage['service']->cluster($distanceMatrix);
            $evaluation = $this->silhouette->evaluateCuts($merges, $distanceMatrix);
            $executionTimeNanoseconds = hrtime(true) - $startedAt;

            $results[] = [
                'algorithm' => $algorithm,
                'label' => $linkage['label'],
                'merges' => $merges,
                ...$evaluation,
                'executionTimeNanoseconds' => $executionTimeNanoseconds,
            ];

            if ($evaluation['bestScore'] > $highestSilhouette) {
                $highestSilhouette = $evaluation['bestScore'];
            }
        }

        // Report every method tied for the best internal score instead of implying a false winner.
        $recommendedAlgorithms = [];
        foreach ($results as $result) {
            if (abs($result['bestScore'] - $highestSilhouette) <= 1.0e-12) {
                $recommendedAlgorithms[] = $result['algorithm'];
            }
        }

        $resultArticles = [];
        foreach ($abstracts as $index => $abstract) {
            $coverage = $embeddingData['coverage'][$index];
            $resultArticles[] = [
                'id' => $abstract->articleId,
                'name' => $abstract->articleName,
                'embeddingCoveragePercent' => $coverage['totalTerms'] === 0
                    ? null
                    : round(($coverage['coveredTerms'] / $coverage['totalTerms']) * 100, 1),
            ];
        }

        return Inertia::render('hierarchical-clustering/results', [
            'articles' => $resultArticles,
            'algorithms' => $results,
            'recommendedAlgorithms' => $recommendedAlgorithms,
            'embeddingDimensions' => $embeddingData['dimensions'],
            'distanceMetric' => 'Euclidiana sobre embeddings FastText',
        ]);
    }
}
