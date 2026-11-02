<?php

namespace App\Services\Article\DTO;

/** Conserva un artículo y sus términos normalizados sin mezclar otros abstracts. */
final class PreprocessedAbstractDTO
{
    /** @param array<int, string> $terms */
    public function __construct(
        public readonly int $articleId,
        public readonly string $articleName,
        public readonly array $terms,
        public readonly string $relativePath = '',
    ) {}
}
