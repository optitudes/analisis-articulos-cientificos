<?php

namespace App\Http\Controllers\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\Article\ArticleRequest;
use App\Services\Article\ArticleService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function __construct(private readonly ArticleService $articles) {}

    public function index(): Response
    {
        return Inertia::render('articles/index', [
            'articles' => $this->articles->all(),
        ]);
    }

    public function show(int $article): Response
    {
        return Inertia::render('articles/show', [
            'article' => $this->articles->findOrFail($article),
        ]);
    }

    public function edit(int $article): Response
    {
        return Inertia::render('articles/edit', [
            'article' => $this->articles->findOrFail($article),
        ]);
    }

    public function update(ArticleRequest $request, int $article): RedirectResponse
    {
        $updatedArticle = $this->articles->update($article, $request->validated());

        return to_route('articles.show', $updatedArticle->id);
    }

    public function destroy(int $article): RedirectResponse
    {
        $this->articles->delete($article);

        return to_route('articles.index');
    }
}
