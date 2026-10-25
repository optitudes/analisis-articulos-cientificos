<?php

use App\Http\Controllers\Article\ArticleController;
use App\Http\Controllers\TermRanking\TermRankingController; // Imports the authenticated term-ranking page actions.
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

        Route::get('term-rankings', [TermRankingController::class, 'index'])->name('term-rankings.index'); // Shows the article-selection form.
        Route::get('term-rankings/results', [TermRankingController::class, 'results'])->name('term-rankings.results'); // Validates selections and renders the computed ranking.
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
