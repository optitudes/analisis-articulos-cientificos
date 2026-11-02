<?php

namespace Tests\Feature;

use App\Models\Article\Article;
use App\Models\User;
use App\Services\Article\ArticleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TextSimilarityTest extends TestCase
{
    use RefreshDatabase;

    private string $embeddingArtifactPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->embeddingArtifactPath = storage_path('framework/testing/embeddings-'.uniqid('', true).'.json');
        config(['embeddings.artifact_path' => $this->embeddingArtifactPath]);
    }

    protected function tearDown(): void
    {
        File::delete($this->embeddingArtifactPath);

        parent::tearDown();
    }

    public function test_guests_are_redirected_from_text_similarity_pages(): void
    {
        $this->get(route('text-similarity.index'))->assertRedirect(route('login'));
        $this->get(route('text-similarity.results'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_open_the_article_selection_page(): void
    {
        Article::query()->create([
            'name' => 'Article A',
            'abstract' => 'The abstract',
            'relative_path' => 'articles/a.pdf',
        ]);
        Article::query()->create([
            'name' => 'Article B',
            'abstract' => 'Another abstract',
            'relative_path' => 'articles/b.pdf',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('text-similarity.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('text-similarity/index', false)
                ->has('articles', 2)
                ->where('availableEmbeddingModels.word2vec', false)
                ->where('availableEmbeddingModels.fasttext', false)
                ->where('articles.0.name', 'Article A'));
    }

    public function test_export_command_uses_seeded_articles_and_the_shared_preprocessor(): void
    {
        Article::query()->create([
            'name' => 'Artículo de prueba',
            'abstract' => 'Árbol and AI',
            'relative_path' => 'articles/prueba.pdf',
        ]);

        $this->artisan('embeddings:export-corpus', ['--output' => $this->embeddingArtifactPath])
            ->assertSuccessful();

        $export = json_decode(File::get($this->embeddingArtifactPath), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(1, $export['schema_version']);
        $this->assertSame('articles/prueba.pdf', $export['articles'][0]['relative_path']);
        $this->assertSame(['arbol', 'ai'], $export['articles'][0]['terms']);
    }

    public function test_abstracts_are_preprocessed_independently_and_all_algorithms_return_matrices(): void
    {
        $firstArticle = Article::query()->create([
            'name' => 'Article A',
            'abstract' => 'The AI and education!',
            'relative_path' => 'articles/a.pdf',
        ]);
        $secondArticle = Article::query()->create([
            'name' => 'Article B',
            'abstract' => 'AI with research',
            'relative_path' => 'articles/b.pdf',
        ]);
        $thirdArticle = Article::query()->create([
            'name' => 'Article C',
            'abstract' => null,
            'relative_path' => 'articles/c.pdf',
        ]);

        $abstracts = app(ArticleService::class)->preprocessAbstracts([
            $firstArticle->id,
            $secondArticle->id,
            $thirdArticle->id,
        ]);

        $this->assertSame(['ai', 'education'], $abstracts[0]->terms);
        $this->assertSame(['ai', 'research'], $abstracts[1]->terms);
        $this->assertSame([], $abstracts[2]->terms);

        $user = User::factory()->create();
        $articleIds = [$firstArticle->id, $secondArticle->id, $thirdArticle->id];
        $expectedDirections = [
            'levenshtein' => 'lower',
            'needleman-wunsch' => 'higher',
            'tf-idf-cosine' => 'higher',
            'jaccard' => 'higher',
        ];

        foreach ($expectedDirections as $algorithm => $direction) {
            $this->actingAs($user)
                ->get(route('text-similarity.results', [
                    'article_ids' => $articleIds,
                    'algorithm' => $algorithm,
                ]))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('text-similarity/results', false)
                    ->where('algorithm', $algorithm)
                    ->where('metricDirection', $direction)
                    ->has('articles', 3)
                    ->has('matrix', 3)
                    ->has('matrix.0', 3)
                    ->has('matrix.1', 3)
                    ->has('matrix.2', 3)
                    ->has('visualSimilarityMatrix', 3)
                    ->has('visualSimilarityMatrix.0', 3)
                    ->where('visualSimilarityMatrix.0.2', 0)
                    ->has('executionTimeNanoseconds'));
        }
    }

    public function test_word2vec_and_fasttext_return_cosine_and_euclidean_embedding_matrices(): void
    {
        $firstArticle = Article::query()->create([
            'name' => 'Article A',
            'abstract' => 'AI education',
            'relative_path' => 'articles/a.pdf',
        ]);
        $secondArticle = Article::query()->create([
            'name' => 'Article B',
            'abstract' => 'AI research',
            'relative_path' => 'articles/b.pdf',
        ]);

        File::ensureDirectoryExists(dirname($this->embeddingArtifactPath));
        File::put($this->embeddingArtifactPath, json_encode([
            'schema_version' => 1,
            'models' => [
                'word2vec' => [
                    'dimensions' => 2,
                    'articles' => [
                        'articles/a.pdf' => ['vector' => [1, 0], 'covered_terms' => 2, 'total_terms' => 2],
                        'articles/b.pdf' => ['vector' => [0, 1], 'covered_terms' => 2, 'total_terms' => 2],
                    ],
                ],
                'fasttext' => [
                    'dimensions' => 2,
                    'articles' => [
                        'articles/a.pdf' => ['vector' => [1, 0], 'covered_terms' => 2, 'total_terms' => 2],
                        'articles/b.pdf' => ['vector' => [1, 1], 'covered_terms' => 2, 'total_terms' => 2],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $user = User::factory()->create();
        $articleIds = [$firstArticle->id, $secondArticle->id];

        foreach ([
            'word2vec-cosine' => ['score' => 0, 'direction' => 'higher', 'visual' => 0],
            'fasttext-cosine' => ['score' => 0.5 / sqrt(0.5), 'direction' => 'higher', 'visual' => 0.5 / sqrt(0.5)],
            'word2vec-euclidean' => ['score' => sqrt(2), 'direction' => 'lower', 'visual' => 0],
            'fasttext-euclidean' => ['score' => 1, 'direction' => 'lower', 'visual' => 0],
        ] as $algorithm => $expected) {
            $this->actingAs($user)
                ->get(route('text-similarity.results', [
                    'article_ids' => $articleIds,
                    'algorithm' => $algorithm,
                ]))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('text-similarity/results', false)
                    ->where('algorithm', $algorithm)
                    ->where('metricDirection', $expected['direction'])
                    ->where('embeddingDimensions', 2)
                    ->where('matrix.0.1', $expected['score'])
                    ->where('matrix.1.0', $expected['score'])
                    ->where('visualSimilarityMatrix.0.1', $expected['visual'])
                    ->where('articles.0.embeddingCoveragePercent', 100));
        }
    }
}
