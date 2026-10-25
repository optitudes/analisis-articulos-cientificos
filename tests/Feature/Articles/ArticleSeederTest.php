<?php

namespace Tests\Feature\Articles;

use App\Models\Article\Article;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_abstracts_verbatim_and_refreshes_existing_rows(): void
    {
        $abstractsByFilename = require base_path('database/seeders/data/article_abstracts.php');
        $seeder = app(ArticleSeeder::class);
        $seeder->run();

        $this->assertSame(count($abstractsByFilename), Article::query()->count());
        $this->assertSame(
            count($abstractsByFilename),
            Article::query()
                ->whereNotNull('abstract')
                ->where('abstract', '<>', '')
                ->count(),
        );

        foreach ($abstractsByFilename as $filename => $abstract) {
            $this->assertSame(
                $abstract,
                Article::query()->where('relative_path', 'articles/'.$filename)->value('abstract'),
                'The seeded abstract must exactly match its static source: '.$filename,
            );
        }

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
