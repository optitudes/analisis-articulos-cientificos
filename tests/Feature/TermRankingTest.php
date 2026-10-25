<?php

// Opens the PHP source file.

namespace Tests\Feature; // Places the test in the feature-test namespace.

use App\Models\Article\Article; // Imports the article model used to prepare test records.
use App\Models\User; // Imports the authenticated user model.
use Illuminate\Foundation\Testing\RefreshDatabase; // Resets the test database between feature tests.
use Tests\TestCase; // Imports the application's HTTP testing base class.

class TermRankingTest extends TestCase // Covers the authenticated term-ranking flow.
{
    // Opens the feature-test class.
    use RefreshDatabase; // Keeps each feature test isolated from other database state.

    public function test_guests_are_redirected_from_the_ranking_pages(): void // Verifies that both ranking endpoints require authentication.
    { // Opens the guest-access test.
        $this->get(route('term-rankings.index'))->assertRedirect(route('login')); // Requires login before showing article choices.
        $this->get(route('term-rankings.results'))->assertRedirect(route('login')); // Requires login before running the ranking.
    } // Closes the guest-access test.

    public function test_authenticated_users_can_view_all_articles_for_selection(): void // Verifies that the selector receives all article names.
    { // Opens the article-selection test.
        Article::query()->create(['name' => 'Artículo A', 'abstract' => 'Resumen A', 'relative_path' => 'articles/a.pdf']); // Creates the first selectable article.
        Article::query()->create(['name' => 'Artículo B', 'abstract' => 'Resumen B', 'relative_path' => 'articles/b.pdf']); // Creates the second selectable article.

        $this->actingAs(User::factory()->create()) // Authenticates a user for the protected page.
            ->get(route('term-rankings.index')) // Requests the ranking selection page.
            ->assertOk() // Confirms the page is available.
            ->assertInertia(fn ($page) => $page // Inspects the Inertia page and its props.
                ->component('term-rankings/index', false) // Confirms the expected React selection component.
                ->has('articles', 2) // Confirms every article is available for selection.
                ->where('articles.0.name', 'Artículo A')); // Confirms article labels are sent to the frontend.
    } // Closes the article-selection test.

    public function test_results_normalize_abstracts_and_rank_terms_by_frequency(): void // Verifies preparation, ranking, tie-breaking, and result props.
    { // Opens the ranking-result test.
        $firstArticle = Article::query()->create([ // Creates the first article used in the combined abstract.
            'name' => 'Artículo de álgebra', // Sets the article's display name.
            'abstract' => 'Árbol árbol MANZANA, sol.', // Includes accents, uppercase, repetition, and punctuation.
            'relative_path' => 'articles/algebra.pdf', // Supplies the required relative document path.
        ]); // Completes creation of the first article.
        $secondArticle = Article::query()->create([ // Creates the second article used in the combined abstract.
            'name' => 'Artículo de botánica', // Sets the article's display name.
            'abstract' => 'manzana árbol sol sol.', // Adds occurrences across a second abstract.
            'relative_path' => 'articles/botanica.pdf', // Supplies the required relative document path.
        ]); // Completes creation of the second article.

        $this->actingAs(User::factory()->create()) // Authenticates a user for the ranking request.
            ->get(route('term-rankings.results', [ // Requests the result page with its validated query parameters.
                'article_ids' => [$firstArticle->id, $secondArticle->id], // Selects both articles for a combined analysis.
                'algorithm' => 'counting-sort', // Chooses the implemented Counting Sort algorithm.
            ])) // Completes the request URL.
            ->assertOk() // Confirms the ranking result page is returned.
            ->assertInertia(fn ($page) => $page // Inspects the result component and calculated props.
                ->component('term-rankings/results', false) // Confirms the dedicated results component is rendered.
                ->where('ranking.0.term', 'arbol') // Confirms accents and uppercase are normalized and alphabetically break the top tie.
                ->where('ranking.0.frequency', 3) // Confirms the normalized tree term is counted across both abstracts.
                ->where('ranking.1.term', 'sol') // Confirms the second term tied at the highest frequency.
                ->where('ranking.1.frequency', 3) // Confirms the second top-term frequency.
                ->where('ranking.2.term', 'manzana') // Confirms the lower-frequency term follows the top group.
                ->where('ranking.2.frequency', 2) // Confirms punctuation and case do not split the term count.
                ->has('executionTimeNanoseconds') // Confirms that the algorithm-only duration is included.
                ->has('articles', 2)); // Confirms selected article labels are included in the response.
    } // Closes the ranking-result test.

    public function test_the_results_require_at_least_two_distinct_articles(): void // Verifies backend enforcement of the minimum selection.
    { // Opens the minimum-selection validation test.
        $article = Article::query()->create(['name' => 'Artículo único', 'abstract' => 'texto', 'relative_path' => 'articles/unico.pdf']); // Creates one valid selectable article.

        $this->actingAs(User::factory()->create()) // Authenticates a user for the invalid request.
            ->from(route('term-rankings.index')) // Sets the selection page as the validation-error destination.
            ->get(route('term-rankings.results', [ // Requests results with too few article identifiers.
                'article_ids' => [$article->id], // Supplies only one selected article.
                'algorithm' => 'counting-sort', // Supplies a supported algorithm to isolate the selection error.
            ])) // Completes the invalid request URL.
            ->assertRedirect(route('term-rankings.index')) // Returns the user to the article-selection page.
            ->assertSessionHasErrors('article_ids'); // Confirms the minimum-selection validation error is present.
    } // Closes the minimum-selection validation test.
} // Closes the feature-test class.
