<?php

// Opens the PHP source file.

namespace Tests\Unit; // Places the test in the unit-test namespace.

use App\Services\Sorting\CountingSortService; // Imports the service under test.
use Tests\TestCase; // Imports the application's PHPUnit base test case.

class CountingSortServiceTest extends TestCase // Defines focused tests for the Counting Sort service.
{
    // Opens the test class.
    public function test_it_ranks_by_frequency_and_breaks_ties_alphabetically(): void // Verifies descending frequency and deterministic ties.
    { // Opens the ranking behavior test.
        $result = (new CountingSortService)->rank(['pera', 'manzana', 'uva', 'manzana', 'pera', 'banana', 'uva', 'pera']); // Runs the algorithm on unsorted tokens.

        $this->assertSame([ // Compares every produced row with the expected ranking.
            ['term' => 'pera', 'frequency' => 3], // Expects the most frequent term first.
            ['term' => 'manzana', 'frequency' => 2], // Expects the first alphabetical term among the frequency-two tie.
            ['term' => 'uva', 'frequency' => 2], // Expects the second alphabetical term among the frequency-two tie.
            ['term' => 'banana', 'frequency' => 1], // Expects the least frequent term last.
        ], $result['ranking']); // Asserts the algorithm's ranking output.
        $this->assertGreaterThanOrEqual(0, $result['elapsedTimeNanoseconds']); // Ensures the algorithm reports a nonnegative measured duration.
    } // Closes the ranking behavior test.

    public function test_it_returns_an_empty_ranking_for_an_empty_input(): void // Verifies the empty-input edge case.
    { // Opens the empty-input test.
        $result = (new CountingSortService)->rank([]); // Runs the algorithm without any terms.

        $this->assertSame([], $result['ranking']); // Confirms that no rows are produced for empty input.
        $this->assertGreaterThanOrEqual(0, $result['elapsedTimeNanoseconds']); // Confirms that empty input still has a measured duration.
    } // Closes the empty-input test.
} // Closes the test class.
