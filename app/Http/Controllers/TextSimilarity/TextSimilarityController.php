<?php

namespace App\Http\Controllers\TextSimilarity;

use App\Http\Controllers\Controller;
use App\Http\Requests\TextSimilarity\TextSimilarityRequest;
use App\Services\Article\ArticleService;
use App\Services\Article\DTO\PreprocessedAbstractDTO;
use App\Services\Similarity\FastTextCosineSimilarityService;
use App\Services\Similarity\FastTextEuclideanDistanceService;
use App\Services\Similarity\JaccardSimilarityService;
use App\Services\Similarity\LevenshteinSimilarityService;
use App\Services\Similarity\NeedlemanWunschSimilarityService;
use App\Services\Similarity\PrecomputedEmbeddingStore;
use App\Services\Similarity\TfIdfCosineSimilarityService;
use App\Services\Similarity\Word2VecCosineSimilarityService;
use App\Services\Similarity\Word2VecEuclideanDistanceService;
use Inertia\Inertia;
use Inertia\Response;

/** Coordinates article selection, pairwise calculations, and their common response format. */
class TextSimilarityController extends Controller
{
    public function __construct(
        private readonly ArticleService $articles,
        private readonly LevenshteinSimilarityService $levenshtein,
        private readonly NeedlemanWunschSimilarityService $needlemanWunsch,
        private readonly TfIdfCosineSimilarityService $tfIdfCosine,
        private readonly JaccardSimilarityService $jaccard,
        private readonly Word2VecCosineSimilarityService $word2vec,
        private readonly FastTextCosineSimilarityService $fastText,
        private readonly Word2VecEuclideanDistanceService $word2vecEuclidean,
        private readonly FastTextEuclideanDistanceService $fastTextEuclidean,
        private readonly PrecomputedEmbeddingStore $embeddings,
    ) {}

    public function index(): Response
    {
        return Inertia::render('text-similarity/index', [
            'articles' => $this->articles->rankingArticles(),
            'availableEmbeddingModels' => [
                'word2vec' => $this->embeddings->isAvailable('word2vec'),
                'fasttext' => $this->embeddings->isAvailable('fasttext'),
            ],
        ]);
    }

    public function results(TextSimilarityRequest $request): Response
    {
        $validated = $request->validated();
        $articleIds = array_map('intval', $validated['article_ids']);
        $abstracts = $this->articles->preprocessAbstracts($articleIds);

        if (str_starts_with($validated['algorithm'], 'word2vec-') && ! $this->embeddings->isAvailable('word2vec')) {
            abort(503, 'Genera los embeddings Word2Vec antes de ejecutar este algoritmo.');
        }
        if (str_starts_with($validated['algorithm'], 'fasttext-') && ! $this->embeddings->isAvailable('fasttext')) {
            abort(503, 'Genera los embeddings FastText antes de ejecutar este algoritmo.');
        }

        // Deja el preprocesamiento fuera del cronómetro para medir solo el algoritmo seleccionado.
        $startedAt = hrtime(true);
        $result = match ($validated['algorithm']) {
            'levenshtein' => [
                'matrix' => $this->levenshtein->compare($abstracts),
                'metric' => 'Distancia de Levenshtein',
                'metricDirection' => 'lower',
            ],
            'needleman-wunsch' => [
                'matrix' => $this->needlemanWunsch->compare($abstracts),
                'metric' => 'Puntaje de alineamiento Needleman–Wunsch',
                'metricDirection' => 'higher',
            ],
            'tf-idf-cosine' => [
                'matrix' => $this->tfIdfCosine->compare($abstracts),
                'metric' => 'Similitud del coseno con TF-IDF',
                'metricDirection' => 'higher',
            ],
            'jaccard' => [
                'matrix' => $this->jaccard->compare($abstracts),
                'metric' => 'Coeficiente de Jaccard',
                'metricDirection' => 'higher',
            ],
            'word2vec-cosine' => [
                ...$this->word2vec->compare($abstracts),
                'metric' => 'Similitud del coseno con embeddings Word2Vec',
                'metricDirection' => 'higher',
            ],
            'fasttext-cosine' => [
                ...$this->fastText->compare($abstracts),
                'metric' => 'Similitud del coseno con embeddings FastText',
                'metricDirection' => 'higher',
            ],
            'word2vec-euclidean' => [
                ...$this->word2vecEuclidean->compare($abstracts),
                'metric' => 'Distancia euclidiana entre embeddings Word2Vec',
                'metricDirection' => 'lower',
            ],
            'fasttext-euclidean' => [
                ...$this->fastTextEuclidean->compare($abstracts),
                'metric' => 'Distancia euclidiana entre embeddings FastText',
                'metricDirection' => 'lower',
            ],
        };
        $executionTimeNanoseconds = hrtime(true) - $startedAt;

        // Normaliza solo la intensidad del color; la matriz conserva el puntaje original del algoritmo.
        $visualSimilarityMatrix = $this->buildVisualSimilarityMatrix(
            $validated['algorithm'],
            $result['matrix'],
            $abstracts,
        );

        $articles = [];
        foreach ($abstracts as $index => $abstract) {
            $article = [
                'id' => $abstract->articleId,
                'name' => $abstract->articleName,
            ];

            if (isset($result['coverage'][$index])) {
                $coverage = $result['coverage'][$index];
                $article['embeddingCoveragePercent'] = $coverage['totalTerms'] === 0
                    ? null
                    : round(($coverage['coveredTerms'] / $coverage['totalTerms']) * 100, 1);
            }

            $articles[] = $article;
        }

        return Inertia::render('text-similarity/results', [
            'algorithm' => $validated['algorithm'],
            'metric' => $result['metric'],
            'metricDirection' => $result['metricDirection'],
            'matrix' => $result['matrix'],
            'visualSimilarityMatrix' => $visualSimilarityMatrix,
            'embeddingDimensions' => $result['dimensions'] ?? null,
            'executionTimeNanoseconds' => $executionTimeNanoseconds,
            'articles' => $articles,
        ]);
    }

    /**
     * Converts native metrics into a 0-to-1 value used only to color the frontend cells.
     *
     * @param  array<int, array<int, int|float>>  $matrix
     * @param  array<int, PreprocessedAbstractDTO>  $abstracts
     * @return array<int, array<int, float>>
     */
    private function buildVisualSimilarityMatrix(string $algorithm, array $matrix, array $abstracts): array
    {
        if (in_array($algorithm, ['word2vec-euclidean', 'fasttext-euclidean'], true)) {
            return $this->normalizeDistanceMatrixForColor($matrix);
        }

        $visualMatrix = [];
        $articleCount = count($abstracts);

        for ($row = 0; $row < $articleCount; $row++) {
            if (! isset($visualMatrix[$row])) {
                $visualMatrix[$row] = [];
            }

            for ($column = $row; $column < $articleCount; $column++) {
                $visualScore = $this->normalizeVisualScore(
                    $algorithm,
                    $matrix[$row][$column],
                    count($abstracts[$row]->terms),
                    count($abstracts[$column]->terms),
                );

                $visualMatrix[$row][$column] = $visualScore;
                $visualMatrix[$column][$row] = $visualScore;
            }
        }

        return $visualMatrix;
    }

    /** @param array<int, array<int, int|float>> $matrix
     * @return array<int, array<int, float>>
     */
    private function normalizeDistanceMatrixForColor(array $matrix): array
    {
        $maximumDistance = 0.0;

        foreach ($matrix as $row) {
            foreach ($row as $distance) {
                if ($distance > $maximumDistance) {
                    $maximumDistance = (float) $distance;
                }
            }
        }

        $visualMatrix = [];
        foreach ($matrix as $rowIndex => $row) {
            $visualMatrix[$rowIndex] = [];

            foreach ($row as $columnIndex => $distance) {
                // The farthest pair in this result is red; zero distance is green.
                $visualMatrix[$rowIndex][$columnIndex] = $maximumDistance === 0.0
                    ? 1.0
                    : 1 - ($distance / $maximumDistance);
            }
        }

        return $visualMatrix;
    }

    private function normalizeVisualScore(
        string $algorithm,
        int|float $score,
        int $firstTermCount,
        int $secondTermCount,
    ): float {
        $longestAbstract = max($firstTermCount, $secondTermCount);

        // Cosine and Jaccard use their native scores for color; negative cosine maps to red.
        if (in_array($algorithm, ['tf-idf-cosine', 'jaccard', 'word2vec-cosine', 'fasttext-cosine'], true)) {
            if ($score < 0) {
                return 0.0;
            }

            if ($score > 1) {
                return 1.0;
            }

            return (float) $score;
        }

        if ($longestAbstract === 0) {
            return 1.0;
        }

        if ($algorithm === 'levenshtein') {
            $visualScore = 1 - ($score / $longestAbstract);
        } else {
            $visualScore = ($score + $longestAbstract) / (2 * $longestAbstract);
        }

        if ($visualScore < 0) {
            return 0.0;
        }

        if ($visualScore > 1) {
            return 1.0;
        }

        return (float) $visualScore;
    }
}
