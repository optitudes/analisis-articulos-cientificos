<?php

use App\Http\Controllers\Article\ArticleController;
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
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
