<?php

namespace Tests\Feature\Articles;

use App\Models\Article\Article;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_all_pdf_abstracts_and_refreshes_existing_rows(): void
    {
        $seeder = app(ArticleSeeder::class);
        $seeder->run();

        $this->assertSame(20, Article::query()->count());
        $this->assertSame(
            20,
            Article::query()
                ->whereNotNull('abstract')
                ->where('abstract', '<>', '')
                ->count(),
        );

        $article = Article::query()
            ->where('relative_path', 'like', 'articles/14.%')
            ->firstOrFail();

        $this->assertGreaterThan(500, mb_strlen($article->abstract));
        $this->assertStringContainsString('enhancing educators', $article->abstract);

        // A prior seed may have created this record before abstracts were added.
        $article->update(['abstract' => null]);
        $seeder->run();

        $this->assertNotEmpty($article->fresh()->abstract);
        $this->assertSame(20, Article::query()->count());
    }
}
