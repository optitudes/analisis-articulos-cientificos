<?php

namespace Tests\Unit;

use App\Models\Article\Article;
use Tests\TestCase;

class ArticlePathTest extends TestCase
{
    public function test_it_appends_the_public_url_to_the_relative_path(): void
    {
        config(['app.url' => 'https://example.test/']);

        $article = new Article(['relative_path' => '/articles/sample.pdf']);

        $this->assertSame('https://example.test/articles/sample.pdf', $article->path);
        $this->assertContains('path', $article->getAppends());
    }
}
