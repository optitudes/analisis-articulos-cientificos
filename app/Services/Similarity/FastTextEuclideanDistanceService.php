<?php

namespace App\Services\Similarity;

use App\Services\Article\DTO\PreprocessedAbstractDTO;

/** Calcula distancias euclidianas entre embeddings FastText precomputados. */
class FastTextEuclideanDistanceService
{
    public function __construct(
        private readonly PrecomputedEmbeddingStore $embeddings,
        private readonly EuclideanDistanceMatrixBuilder $matrixBuilder,
    ) {}

    /** @param array<int, PreprocessedAbstractDTO> $abstracts
     * @return array{matrix: array<int, array<int, float>>, coverage: array<int, array{coveredTerms: int, totalTerms: int}>, dimensions: int}
     */
    public function compare(array $abstracts): array
    {
        $data = $this->embeddings->forArticles('fasttext', $abstracts);

        return [
            'matrix' => $this->matrixBuilder->build($data['vectors']),
            'coverage' => $data['coverage'],
            'dimensions' => $data['dimensions'],
        ];
    }
}
