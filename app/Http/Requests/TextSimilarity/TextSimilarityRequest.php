<?php

namespace App\Http\Requests\TextSimilarity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates the selected articles and textual-similarity algorithm. */
class TextSimilarityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'article_ids' => ['required', 'array', 'min:2'],
            'article_ids.*' => ['required', 'integer', 'distinct', 'exists:articles,id'],
            'algorithm' => [
                'required',
                'string',
                Rule::in([
                    'levenshtein',
                    'needleman-wunsch',
                    'tf-idf-cosine',
                    'jaccard',
                    'word2vec-cosine',
                    'fasttext-cosine',
                    'word2vec-euclidean',
                    'fasttext-euclidean',
                ]),
            ],
        ];
    }
}
