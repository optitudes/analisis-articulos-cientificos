<?php

namespace Tests\Feature\Articles;

use App\Models\Article\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_articles_crud(): void
    {
        $this->get(route('articles.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_update_and_delete_articles(): void
    {
        $this->actingAs(User::factory()->create());

        $article = Article::query()->create([
            'name' => 'Artículo de prueba',
            'abstract' => null,
            'relative_path' => 'articles/test.pdf',
        ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'name' => 'Artículo de prueba',
            'abstract' => null,
            'relative_path' => 'articles/test.pdf',
        ]);

        $this->get(route('articles.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('articles/index', false)
                ->has('articles', 1)
                ->where('articles.0.path', rtrim(config('app.url'), '/').'/articles/test.pdf'));

        $this->get(route('articles.show', $article))->assertOk()->assertInertia(
            fn ($page) => $page->component('articles/show', false)->where('article.id', $article->id),
        );

        $this->get(route('articles.edit', $article))->assertOk()->assertInertia(
            fn ($page) => $page->component('articles/edit', false)->where('article.name', 'Artículo de prueba'),
        );

        $this->put(route('articles.update', $article), [
            'name' => 'Artículo actualizado',
            'abstract' => 'Resumen agregado',
            'relative_path' => 'articles/updated.pdf',
        ])->assertRedirect(route('articles.show', $article));

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'name' => 'Artículo actualizado',
            'abstract' => 'Resumen agregado',
            'relative_path' => 'articles/updated.pdf',
        ]);

        $this->delete(route('articles.destroy', $article))->assertRedirect(route('articles.index'));

        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }

    public function test_articles_cannot_be_created_through_the_web_routes(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/dashboard/articles/create')->assertNotFound();
        $this->post('/dashboard/articles', [
            'name' => 'Artículo no permitido',
            'relative_path' => 'articles/not-allowed.pdf',
        ])->assertStatus(405);
    }
}
