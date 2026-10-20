<?php

namespace App\Services\Article;

use App\Models\Article\Article;
use Illuminate\Database\Eloquent\Collection;

class ArticleService
{
    /** @return Collection<int, Article> */
    public function all(): Collection
    {
        return Article::query()->orderBy('name')->get();
    }

    public function findOrFail(int $id): Article
    {
        return Article::query()->findOrFail($id);
    }

    /** @param array{name: string, abstract?: string|null, relative_path: string} $data */
    public function update(int $id, array $data): Article
    {
        $article = $this->findOrFail($id);
        $article->update($data);

        return $article->refresh();
    }

    public function delete(int $id): void
    {
        $this->findOrFail($id)->delete();
    }
}
