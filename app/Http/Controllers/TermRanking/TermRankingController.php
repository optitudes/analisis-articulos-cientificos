<?php

// Opens the PHP source file.

namespace App\Http\Controllers\TermRanking; // Places term-ranking endpoints in their own controller namespace.

use App\Http\Controllers\Controller; // Imports the shared Laravel controller base class.
use App\Http\Requests\TermRanking\TermRankingRequest; // Imports validation for article selection and algorithm choice.
use App\Services\Article\ArticleService; // Imports the article retrieval and text-preparation service.
use App\Services\Sorting\CountingSortService; // Imports the measured Counting Sort implementation.
use Inertia\Inertia; // Imports the server-side Inertia page renderer.
use Inertia\Response; // Imports the response type returned to the React frontend.

class TermRankingController extends Controller // Coordinates the term-ranking selection and result pages.
{
    // Opens the controller class.
    public function __construct(// Declares the services required by both controller actions.
        private readonly ArticleService $articles, // Injects article selection and abstract preparation behavior.
        private readonly CountingSortService $countingSort, // Injects the Counting Sort ranking algorithm.
    ) { // Opens the constructor body.
    } // Closes the constructor body.

    public function index(): Response // Displays the article and algorithm selection page.
    { // Opens the selection action.
        return Inertia::render('term-rankings/index', [ // Renders the React selection page through Inertia.
            'articles' => $this->articles->rankingArticles(), // Supplies all articles without exposing their abstracts.
        ]); // Completes the selection-page response.
    } // Closes the selection action.

    public function results(TermRankingRequest $request): Response // Validates and displays the requested term ranking.
    { // Opens the results action.
        $validated = $request->validated(); // Retrieves only validated query parameters.
        $articleIds = array_map('intval', $validated['article_ids']); // Converts validated identifiers into integer values.
        $terms = $this->articles->prepareTermsForRanking($articleIds); // Fetches, combines, sanitizes, and tokenizes abstracts before timing.
        $sortResult = $this->countingSort->rank($terms); // Measures only frequency counting, bucket sorting, and tie-breaking.

        return Inertia::render('term-rankings/results', [ // Renders the separate React results page.
            'ranking' => $sortResult['ranking'], // Sends terms ordered by descending frequency and alphabetic ties.
            'executionTimeNanoseconds' => $sortResult['elapsedTimeNanoseconds'], // Sends the algorithm-only runtime for display.
            'articles' => $this->articles->rankingArticlesByIds($articleIds), // Supplies names of the articles included in this ranking.
        ]); // Completes the results-page response.
    } // Closes the results action.
} // Closes the controller class.
