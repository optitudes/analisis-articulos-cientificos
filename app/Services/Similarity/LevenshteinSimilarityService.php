<?php

namespace App\Services\Similarity;

use App\Services\Article\DTO\PreprocessedAbstractDTO;

/** Construye una matriz de distancias Levenshtein entre secuencias de términos. */
class LevenshteinSimilarityService
{
    /** @param array<int, PreprocessedAbstractDTO> $abstracts
     * @return array<int, array<int, int>>
     */
    public function compare(array $abstracts): array
    {
        $matrix = [];
        $articleCount = count($abstracts);

        // Calcula solo la mitad superior y refleja cada distancia para mantener simétrica la matriz.
        for ($row = 0; $row < $articleCount; $row++) {
            if (! isset($matrix[$row])) {
                $matrix[$row] = [];
            }

            for ($column = $row; $column < $articleCount; $column++) {
                $distance = $this->distance($abstracts[$row]->terms, $abstracts[$column]->terms);
                $matrix[$row][$column] = $distance;
                $matrix[$column][$row] = $distance;
            }
        }

        return $matrix;
    }

    /** @param array<int, string> $firstTerms
     * @param array<int, string> $secondTerms
     */
    public function distance(array $firstTerms, array $secondTerms): int
    {
        $firstLength = count($firstTerms);
        $secondLength = count($secondTerms);
        $previousRow = [];

        // Distancia de una lista vacía a cada prefijo de la segunda lista.
        for ($column = 0; $column <= $secondLength; $column++) {
            $previousRow[$column] = $column;
        }

        // Cada celda elige entre eliminar, insertar o conservar/reemplazar un término.
        for ($row = 1; $row <= $firstLength; $row++) {
            $currentRow = [];
            $currentRow[0] = $row;

            for ($column = 1; $column <= $secondLength; $column++) {
                $replacementCost = $firstTerms[$row - 1] === $secondTerms[$column - 1] ? 0 : 1;
                $deletion = $previousRow[$column] + 1;
                $insertion = $currentRow[$column - 1] + 1;
                $replacement = $previousRow[$column - 1] + $replacementCost;

                $smallest = $deletion;
                if ($insertion < $smallest) {
                    $smallest = $insertion;
                }
                if ($replacement < $smallest) {
                    $smallest = $replacement;
                }

                $currentRow[$column] = $smallest;
            }

            $previousRow = $currentRow;
        }

        return $previousRow[$secondLength];
    }
}
