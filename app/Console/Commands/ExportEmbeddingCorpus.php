<?php

namespace App\Console\Commands;

use App\Models\Article\Article;
use App\Services\Article\ArticleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JsonException;

/** Exporta términos normalizados de la base para generar embeddings fuera del servidor. */
class ExportEmbeddingCorpus extends Command
{
    protected $signature = 'embeddings:export-corpus {--output= : Archivo JSON de salida}';

    protected $description = 'Exporta abstracts sembrados como términos para generar embeddings localmente.';

    public function handle(ArticleService $articles): int
    {
        $articleIds = Article::query()
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($articleIds === []) {
            $this->error('No hay artículos en la base de datos. Ejecuta primero el seeder de artículos.');

            return self::FAILURE;
        }

        $preprocessedAbstracts = $articles->preprocessAbstracts($articleIds);
        $exportedArticles = [];

        foreach ($preprocessedAbstracts as $abstract) {
            $exportedArticles[] = [
                'relative_path' => $abstract->relativePath,
                'name' => $abstract->articleName,
                'terms' => $abstract->terms,
            ];
        }

        $outputPath = $this->option('output') ?: storage_path('app/embeddings/corpus.json');

        try {
            $contents = json_encode(
                ['schema_version' => 1, 'articles' => $exportedArticles],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            $this->error('No fue posible serializar los términos: '.$exception->getMessage());

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $contents.PHP_EOL);

        $this->info('Corpus exportado: '.$outputPath);
        $this->line('Artículos: '.count($exportedArticles));

        return self::SUCCESS;
    }
}
