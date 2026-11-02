<?php

namespace App\Services\Similarity;

use App\Services\Article\DTO\PreprocessedAbstractDTO;

/** Construye una matriz de puntajes de alineamiento global entre términos. */
class NeedlemanWunschSimilarityService
{
    private const MATCH_SCORE = 1;
    private const MISMATCH_SCORE = -1;
    private const GAP_PENALTY = -1;

    /** @param array<int, PreprocessedAbstractDTO> $abstracts
     * @return array<int, array<int, int>>
     */
    public function compare(array $abstracts): array
    {
        $matrix = [];
        $articleCount = count($abstracts);

        // Calcula solo la mitad superior y refleja cada puntaje para mantener simétrica la matriz.
        for ($row = 0; $row < $articleCount; $row++) {
            if (! isset($matrix[$row])) {
                $matrix[$row] = [];
            }

            for ($column = $row; $column < $articleCount; $column++) {
                $score = $this->alignmentScore($abstracts[$row]->terms, $abstracts[$column]->terms);
                $matrix[$row][$column] = $score;
                $matrix[$column][$row] = $score;
            }
        }

        return $matrix;
    }

    /** @param array<int, string> $firstTerms
     * @param array<int, string> $secondTerms
     */
    public function alignmentScore(array $firstTerms, array $secondTerms): int
    {
        $firstLength = count($firstTerms);
        $secondLength = count($secondTerms);
        $scores = [];

        // Inicializa puntajes para alinear cada prefijo usando únicamente gaps.
        for ($row = 0; $row <= $firstLength; $row++) {
            $scores[$row] = [];
            $scores[$row][0] = $row * self::GAP_PENALTY;
        }
        for ($column = 0; $column <= $secondLength; $column++) {
            $scores[0][$column] = $column * self::GAP_PENALTY;
        }

        // Elige entre coincidencia/diferencia, un gap en la segunda lista o un gap en la primera.
        for ($row = 1; $row <= $firstLength; $row++) {
            for ($column = 1; $column <= $secondLength; $column++) {
                $substitutionScore = $firstTerms[$row - 1] === $secondTerms[$column - 1]
                    ? self::MATCH_SCORE
                    : self::MISMATCH_SCORE;

                $diagonal = $scores[$row - 1][$column - 1] + $substitutionScore;
                $up = $scores[$row - 1][$column] + self::GAP_PENALTY;
                $left = $scores[$row][$column - 1] + self::GAP_PENALTY;

                $bestScore = $diagonal;
                if ($up > $bestScore) {
                    $bestScore = $up;
                }
                if ($left > $bestScore) {
                    $bestScore = $left;
                }

                $scores[$row][$column] = $bestScore;
            }
        }

        return $scores[$firstLength][$secondLength];
    }
}
