<?php

namespace Database\Seeders;

use App\Models\Article\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $files = collect(File::files(public_path('articles')))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'pdf')
            ->sortBy(fn ($file) => $file->getFilename(), SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $name = preg_replace('/^\d+\.\s*/u', '', $name) ?? $name;
            $name = Str::of($name)->replaceMatches('/\s+/u', ' ')->trim()->toString();
            $relativePath = 'articles/'.$filename;

            Article::query()->firstOrCreate(
                ['relative_path' => $relativePath],
                ['name' => $name, 'abstract' => null],
            );
        }
    }
}
