<?php

namespace Tests\Feature;

use App\Models\Article\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HierarchicalClusteringTest extends TestCase
{
    use RefreshDatabase;

    private string $embeddingArtifactPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->embeddingArtifactPath = storage_path('framework/testing/clustering-'.uniqid('', true).'.json');
        config(['embeddings.artifact_path' => $this->embeddingArtifactPath]);
    }

    protected function tearDown(): void
    {
        File::delete($this->embeddingArtifactPath);

        parent::tearDown();
    }

    public function test_the_selection_view_lists_seeded_articles_and_embedding_readiness(): void
    {
        Article::query()->create([
            'name' => 'Article A',
            'abstract' => 'AI education',
            'relative_path' => 'articles/a.pdf',
        ]);
        $this->writeFastTextEmbeddings(['articles/a.pdf' => $this->embedding([1, 0])]);

        $this->actingAs(User::factory()->create())
            ->get(route('hierarchical-clustering.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('hierarchical-clustering/index', false)
                ->has('articles', 1)
                ->where('embeddingAvailable', true));
    }

    public function test_all_three_linkages_return_dendrograms_and_silhouette_results(): void
    {
        $articles = [
            ['Article A', 'articles/a.pdf', [0.0, 0.0]],
            ['Article B', 'articles/b.pdf', [0.0, 1.0]],
            ['Article C', 'articles/c.pdf', [10.0, 10.0]],
            ['Article D', 'articles/d.pdf', [10.0, 11.0]],
        ];
        $embeddings = [];

        foreach ($articles as [$name, $relativePath, $vector]) {
            Article::query()->create([
                'name' => $name,
                'abstract' => 'AI education research',
                'relative_path' => $relativePath,
            ]);
            $embeddings[$relativePath] = $this->embedding($vector);
        }

        $this->writeFastTextEmbeddings($embeddings);

        $this->actingAs(User::factory()->create())
            ->get(route('hierarchical-clustering.results'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('hierarchical-clustering/results', false)
                ->has('articles', 4)
                ->where('embeddingDimensions', 2)
                ->has('algorithms', 3)
                ->where('algorithms.0.algorithm', 'single-linkage')
                ->where('algorithms.1.algorithm', 'complete-linkage')
                ->where('algorithms.2.algorithm', 'average-linkage')
                ->has('algorithms.0.merges', 3)
                ->has('algorithms.1.merges', 3)
                ->has('algorithms.2.merges', 3)
                ->has('algorithms.0.scoresByClusterCount', 2)
                ->has('algorithms.0.clusterAssignments', 4)
                ->has('recommendedAlgorithms', 3)
                ->where('articles.0.embeddingCoveragePercent', 100));
    }

    /** @param array<int, float|int> $vector
     * @return array{vector: array<int, float|int>, covered_terms: int, total_terms: int}
     */
    private function embedding(array $vector): array
    {
        return [
            'vector' => $vector,
            'covered_terms' => 3,
            'total_terms' => 3,
        ];
    }

    /** @param array<string, array{vector: array<int, float|int>, covered_terms: int, total_terms: int}> $embeddings */
    private function writeFastTextEmbeddings(array $embeddings): void
    {
        File::ensureDirectoryExists(dirname($this->embeddingArtifactPath));
        File::put($this->embeddingArtifactPath, json_encode([
            'schema_version' => 1,
            'models' => [
                'fasttext' => [
                    'dimensions' => 2,
                    'articles' => $embeddings,
                ],
            ],
        ], JSON_THROW_ON_ERROR));
    }
}
