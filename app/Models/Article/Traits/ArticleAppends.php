<?php

namespace App\Models\Article\Traits;

trait ArticleAppends
{
    protected function initializeArticleAppends(): void
    {
        $this->appends = array_values(array_unique([...$this->appends, 'path']));
    }

    public function getPathAttribute(): ?string
    {
        if (! $this->relative_path) {
            return null;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($this->relative_path, '/');
    }
}
