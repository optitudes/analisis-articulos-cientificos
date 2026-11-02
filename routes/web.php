<?php

use App\Http\Controllers\Article\ArticleController;
use App\Http\Controllers\HierarchicalClustering\HierarchicalClusteringController;
use App\Http\Controllers\TextSimilarity\TextSimilarityController;
use App\Http\Controllers\TermRanking\TermRankingController; // Importa las acciones protegidas del ranking de términos.
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::prefix('dashboard')->group(function () {
        Route::resource('articles', ArticleController::class)
            ->except(['create', 'store'])
            ->where(['article' => '[0-9]+']);

        Route::get('term-rankings', [TermRankingController::class, 'index'])->name('term-rankings.index'); // Muestra el formulario para seleccionar artículos.
        Route::get('term-rankings/results', [TermRankingController::class, 'results'])->name('term-rankings.results'); // Valida la selección y muestra el ranking calculado.

        Route::get('text-similarity', [TextSimilarityController::class, 'index'])->name('text-similarity.index');
        Route::get('text-similarity/results', [TextSimilarityController::class, 'results'])->name('text-similarity.results');

        Route::get('hierarchical-clustering', [HierarchicalClusteringController::class, 'index'])
            ->name('hierarchical-clustering.index');
        Route::get('hierarchical-clustering/results', [HierarchicalClusteringController::class, 'results'])
            ->name('hierarchical-clustering.results');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
