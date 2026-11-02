<?php

namespace Tests\Unit;

use App\Services\Article\DTO\PreprocessedAbstractDTO;
use App\Services\Similarity\EuclideanDistanceMatrixBuilder;
use App\Services\Similarity\JaccardSimilarityService;
use App\Services\Similarity\LevenshteinSimilarityService;
use App\Services\Similarity\NeedlemanWunschSimilarityService;
use App\Services\Similarity\TfIdfCosineSimilarityService;
use PHPUnit\Framework\TestCase;

class TextSimilarityAlgorithmsTest extends TestCase
{
    public function test_euclidean_builder_returns_a_symmetric_matrix_of_native_distances(): void
    {
        $matrix = (new EuclideanDistanceMatrixBuilder())->build([
            [0.0, 0.0],
            [3.0, 4.0],
            [3.0, 0.0],
        ]);

        $this->assertEquals([[0, 5, 3], [5, 0, 4], [3, 4, 0]], $matrix);
        $this->assertEquals($matrix[0][1], $matrix[1][0]);
    }

    public function test_levenshtein_counts_insertions_deletions_and_substitutions(): void
    {
        $service = new LevenshteinSimilarityService();

        $this->assertSame(1, $service->distance(['ai', 'education'], ['ai', 'research']));
        $this->assertSame(2, $service->distance([], ['ai', 'education']));
        $this->assertSame(0, $service->distance([], []));

        $matrix = $service->compare([
            $this->abstract(1, 'A', ['ai', 'education']),
            $this->abstract(2, 'B', ['ai', 'research']),
        ]);
        $this->assertSame([[0, 1], [1, 0]], $matrix);
    }

    public function test_needleman_wunsch_uses_the_configured_match_mismatch_and_gap_scores(): void
    {
        $service = new NeedlemanWunschSimilarityService();

        $this->assertSame(2, $service->alignmentScore(['ai', 'education'], ['ai', 'education']));
        $this->assertSame(0, $service->alignmentScore(['ai', 'education'], ['ai', 'research']));
        $this->assertSame(-2, $service->alignmentScore([], ['ai', 'education']));

        $matrix = $service->compare([
            $this->abstract(1, 'A', ['ai', 'education']),
            $this->abstract(2, 'B', ['ai', 'research']),
        ]);
        $this->assertSame([[2, 0], [0, 2]], $matrix);
    }

    public function test_tfidf_cosine_returns_a_symmetric_matrix_and_zero_for_empty_vectors(): void
    {
        $service = new TfIdfCosineSimilarityService();
        $matrix = $service->compare([
            $this->abstract(1, 'A', ['ai', 'education']),
            $this->abstract(2, 'B', ['ai', 'research']),
            $this->abstract(3, 'C', []),
        ]);

        $this->assertCount(3, $matrix);
        $this->assertEquals(1.0, $matrix[0][0]);
        $this->assertSame($matrix[0][1], $matrix[1][0]);
        $this->assertGreaterThan(0.0, $matrix[0][1]);
        $this->assertSame(0.0, $matrix[2][2]);
        $this->assertSame(0.0, $matrix[0][2]);
    }

    public function test_jaccard_uses_distinct_terms_and_defines_empty_set_similarity(): void
    {
        $service = new JaccardSimilarityService();
        $matrix = $service->compare([
            $this->abstract(1, 'A', ['ai', 'ai', 'education']),
            $this->abstract(2, 'B', ['education', 'research']),
            $this->abstract(3, 'C', []),
        ]);

        $this->assertEquals(1 / 3, $matrix[0][1]);
        $this->assertSame($matrix[0][1], $matrix[1][0]);
        $this->assertSame(1.0, $matrix[2][2]);
        $this->assertSame(0.0, $matrix[0][2]);
    }

    /** @param array<int, string> $terms */
    private function abstract(int $id, string $name, array $terms): PreprocessedAbstractDTO
    {
        return new PreprocessedAbstractDTO($id, $name, $terms);
    }
}
