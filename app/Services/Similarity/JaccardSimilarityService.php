<?php

namespace App\Services\Similarity;

use App\Services\Article\DTO\PreprocessedAbstractDTO;

/** Construye una matriz del coeficiente de Jaccard sobre términos distintos. */
class JaccardSimilarityService
{
    /** @param array<int, PreprocessedAbstractDTO> $abstracts
     * @return array<int, array<int, float>>
     */
    public function compare(array $abstracts): array
    {
        $termSets = [];

        // Representa cada lista como un conjunto para que las repeticiones no alteren Jaccard.
        foreach ($abstracts as $documentIndex => $abstract) {
            $termSets[$documentIndex] = [];
            foreach ($abstract->terms as $term) {
                $termSets[$documentIndex][$term] = true;
            }
        }

        $matrix = [];
        $articleCount = count($abstracts);

        // Cuenta intersección y unión para cada pareja y refleja el resultado en ambas posiciones.
        for ($row = 0; $row < $articleCount; $row++) {
            if (! isset($matrix[$row])) {
                $matrix[$row] = [];
            }

            for ($column = $row; $column < $articleCount; $column++) {
                $intersectionSize = 0;
                foreach ($termSets[$row] as $term => $present) {
                    if (isset($termSets[$column][$term])) {
                        $intersectionSize++;
                    }
                }

                $unionSize = count($termSets[$row]) + count($termSets[$column]) - $intersectionSize;
                $similarity = $unionSize === 0 ? 1.0 : (float) $intersectionSize / $unionSize;

                $matrix[$row][$column] = $similarity;
                $matrix[$column][$row] = $similarity;
            }
        }

        return $matrix;
    }
}
