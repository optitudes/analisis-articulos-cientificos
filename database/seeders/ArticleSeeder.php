<?php

namespace Database\Seeders;

use App\Models\Article\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        // Abstracts are stored as seed data so database seeding never depends on
        // GROBID, network access, or running an extraction process.
        $abstractsByFilename = require __DIR__.'/data/article_abstracts.php';

        foreach ($abstractsByFilename as $filename => $abstract) {
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $name = preg_replace('/^\d+\.\s*/u', '', $name) ?? $name;
            $name = Str::of($name)->replaceMatches('/\s+/u', ' ')->trim()->toString();
            $relativePath = 'articles/'.$filename;

            // updateOrCreate also fills abstracts for article rows that were
            // already seeded with null before the extracted text was added.
            Article::query()->updateOrCreate(
                ['relative_path' => $relativePath],
                [
                    'name' => $name,
                    'abstract' => $abstract,
                ],
            );
        }
    }
}
