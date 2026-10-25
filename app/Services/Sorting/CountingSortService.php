<?php

// Opens the PHP source file.

namespace App\Services\Sorting; // Places the sorter in the application's sorting-service namespace.

class CountingSortService // Defines the Counting Sort implementation used for term ranking.
{
    // Opens the sorting-service class.
    /** @param array<int, string> $terms
     * @return array{ranking: array<int, array{term: string, frequency: int}>, elapsedTimeNanoseconds: int}
     */
    public function rank(array $terms): array // Counts terms, sorts frequencies, and returns the measured runtime.
    { // Opens the timed algorithm entry point.
        $startedAt = hrtime(true); // Starts a monotonic nanosecond timer after all article preprocessing is complete.
        $frequencies = $this->countOccurrences($terms); // Builds each normalized term's occurrence count inside the timed algorithm.
        $buckets = $this->buildFrequencyBuckets($frequencies); // Places terms into buckets indexed by their occurrence counts.
        $ranking = $this->collectRanking($buckets); // Reads buckets from highest frequency to lowest with alphabetic tie-breaking.

        return [ // Returns the ranked rows and the algorithm-only execution time.
            'ranking' => $ranking, // Supplies the term and its total frequency for each result row.
            'elapsedTimeNanoseconds' => hrtime(true) - $startedAt, // Stops the timer after counting and bucket ordering finish.
        ]; // Completes the result payload.
    } // Closes the timed algorithm entry point.

    /** @param array<int, string> $terms
     * @return array<string, int>
     */
    private function countOccurrences(array $terms): array // Counts how many times each sanitized term appears.
    { // Opens the frequency-counting step.
        $frequencies = []; // Initializes the term-to-frequency map.

        foreach ($terms as $term) { // Visits each token from the selected article abstracts once.
            if ($term === '') { // Guards against empty values if the service is called outside its normal pipeline.
                continue; // Skips empty values because they are not terms to rank.
            } // Closes the empty-term guard.

            $frequencies[$term] = ($frequencies[$term] ?? 0) + 1; // Increments the count for the current term.
        } // Closes the token-counting loop.

        return $frequencies; // Returns the occurrence counts used as Counting Sort keys.
    } // Closes the frequency-counting step.

    /** @param array<string, int> $frequencies
     * @return array<int, array<int, string>>
     */
    private function buildFrequencyBuckets(array $frequencies): array // Groups distinct terms by their numeric frequencies.
    { // Opens the bucket-construction step.
        $maximumFrequency = $frequencies === [] ? 0 : max($frequencies); // Finds the largest key while safely handling empty input.
        $buckets = array_fill(0, $maximumFrequency + 1, []); // Allocates one bucket for each possible count from zero to the maximum.

        foreach ($frequencies as $term => $frequency) { // Visits every distinct term and its numeric sorting key.
            $buckets[$frequency][] = $term; // Stores the term at the bucket matching its frequency.
        } // Closes the term-distribution loop.

        return $buckets; // Returns the frequency-indexed buckets for the collection step.
    } // Closes the bucket-construction step.

    /** @param array<int, array<int, string>> $buckets
     * @return array<int, array{term: string, frequency: int}>
     */
    private function collectRanking(array $buckets): array // Emits terms in descending-frequency order.
    { // Opens the ranking-collection step.
        $ranking = []; // Initializes the output list of term-frequency rows.

        for ($frequency = count($buckets) - 1; $frequency > 0; $frequency--) { // Traverses numeric buckets from the highest count to the lowest.
            $termsAtFrequency = $buckets[$frequency]; // Copies the terms sharing the current frequency.
            sort($termsAtFrequency, SORT_STRING); // Orders only equal-frequency terms alphabetically without sorting the input array first.

            foreach ($termsAtFrequency as $term) { // Visits each term in the current frequency bucket.
                $ranking[] = [ // Adds one display-ready ranking row.
                    'term' => $term, // Stores the normalized lowercase term.
                    'frequency' => $frequency, // Stores the number of occurrences across all selected abstracts.
                ]; // Completes the ranking row.
            } // Closes the current bucket's output loop.
        } // Closes the descending-frequency traversal.

        return $ranking; // Returns the final descending-frequency ranking with alphabetic ties.
    } // Closes the ranking-collection step.
} // Closes the sorting-service class.
