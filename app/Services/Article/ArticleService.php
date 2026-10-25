<?php

namespace App\Services\Article;

use App\Models\Article\Article;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str; // Imports Laravel's transliteration utility for normalizing accented text.

class ArticleService
{
    /** @return Collection<int, Article> */
    public function all(): Collection
    {
        return Article::query()->orderBy('name')->get();
    }

    /** Returns the minimum article data needed by the term-ranking selector. */
    public function rankingArticles(): Collection // Exposes article choices without loading their full abstracts.
    { // Starts the article-selection query.
        return Article::query() // Builds an Eloquent query for scientific articles.
            ->select(['id', 'name']) // Limits the response to the identifier and display name.
            ->orderBy('name') // Presents the choices in a predictable name order.
            ->get(); // Executes the query and returns the available articles.
    } // Ends the article-selection query.

    /** Returns selected article names for the ranking result page. */
    public function rankingArticlesByIds(array $articleIds): Collection // Loads display data for the selected articles.
    { // Starts the selected-article query.
        return Article::query() // Builds an Eloquent query for the articles used in the ranking.
            ->select(['id', 'name']) // Avoids loading abstracts a second time.
            ->whereIn('id', $articleIds) // Restricts the query to validated identifiers.
            ->orderBy('name') // Keeps the selected-article list deterministic.
            ->get(); // Executes the query and returns the selected article names.
    } // Ends the selected-article query.

    /** @param array<int, int> $articleIds */
    public function prepareTermsForRanking(array $articleIds): array // Orchestrates text preparation before timed sorting.
    { // Starts the preparation pipeline.
        $abstracts = $this->abstractsForArticles($articleIds); // Reads all selected abstracts in one database query.
        $combinedAbstract = $this->combineAbstracts($abstracts); // Joins the abstracts into one text document.
        $sanitizedAbstract = $this->sanitizeAbstract($combinedAbstract); // Removes case and accent differences before tokenizing.

        return $this->splitAbstractIntoTerms($sanitizedAbstract); // Extracts words without sorting them alphabetically.
    } // Ends the preparation pipeline.

    /** @param array<int, int> $articleIds
     * @return array<int, string|null>
     */
    private function abstractsForArticles(array $articleIds): array // Fetches abstracts for all selected articles together.
    { // Starts the single-query abstract retrieval.
        return Article::query() // Builds the abstract-only Eloquent query.
            ->whereIn('id', $articleIds) // Selects only the articles chosen by the user.
            ->orderBy('id') // Makes concatenation deterministic regardless of query-plan ordering.
            ->pluck('abstract') // Retrieves only the abstract column for each selected article.
            ->all(); // Converts the collection into a plain PHP array.
    } // Ends the single-query abstract retrieval.

    /** @param array<int, string|null> $abstracts */
    private function combineAbstracts(array $abstracts): string // Produces the single text required by the tokenizer.
    { // Starts the abstract concatenation.
        $availableAbstracts = array_filter( // Removes empty abstracts without changing the remaining text.
            $abstracts, // Uses the abstracts retrieved in one query.
            static fn (?string $abstract): bool => $abstract !== null && trim($abstract) !== '', // Skips null and blank summaries.
        ); // Finishes filtering abstracts with no usable content.

        return implode("\n", $availableAbstracts); // Joins all usable abstracts into one large text string.
    } // Ends the abstract concatenation.

    private function sanitizeAbstract(string $abstract): string // Normalizes case and removes diacritics before splitting.
    { // Starts text normalization.
        $lowercaseAbstract = mb_strtolower($abstract, 'UTF-8'); // Converts Unicode letters to lowercase consistently.

        return Str::ascii($lowercaseAbstract); // Transliterate accented letters such as á and ñ to plain ASCII letters.
    } // Ends text normalization.

    /** @return array<int, string> */
    private function splitAbstractIntoTerms(string $abstract): array // Extracts alphabetic terms and discards punctuation.
    { // Starts regex-based tokenization.
        $terms = preg_split( // Splits the normalized abstract into word tokens.
            '/[^a-z]+/u', // Treats every run of non-lowercase-ASCII letters as a separator; punctuation, digits, and whitespace are excluded.
            $abstract, // Uses the lowercase, accent-free text produced by sanitizeAbstract.
            -1, // Applies the expression to the entire abstract rather than limiting the number of splits.
            PREG_SPLIT_NO_EMPTY, // Prevents repeated punctuation or spaces from producing empty terms.
        ); // Finishes the Unicode-aware split operation.

        return $terms === false ? [] : array_values($terms); // Returns a reindexed list and safely handles regex failure.
    } // Ends regex-based tokenization.

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
