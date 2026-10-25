<?php

// Opens the PHP source file.

namespace App\Http\Requests\TermRanking; // Groups validation for the term-ranking feature.

use Illuminate\Foundation\Http\FormRequest; // Imports Laravel's request-validation base class.
use Illuminate\Validation\Rule; // Imports validation rules for the supported algorithm list.

class TermRankingRequest extends FormRequest // Defines validation for ranking query parameters.
{
    // Opens the request class.
    public function authorize(): bool // Allows this request after the authenticated route middleware grants access.
    { // Opens the authorization method.
        return true; // Leaves authentication enforcement to the route's auth middleware.
    } // Closes the authorization method.

    /** @return array<string, array<int, mixed>> */ // Documents the shape of the Laravel validation rules.
    public function rules(): array // Declares the accepted article identifiers and algorithm.
    { // Opens the validation-rules method.
        return [ // Returns the validation rules for the ranking request.
            'article_ids' => ['required', 'array', 'min:2'], // Requires at least two selected article identifiers.
            'article_ids.*' => ['required', 'integer', 'distinct', 'exists:articles,id'], // Ensures each selection is unique and exists.
            'algorithm' => ['required', 'string', Rule::in(['counting-sort'])], // Restricts execution to the implemented algorithm.
        ]; // Closes the validation-rules array.
    } // Closes the validation-rules method.
} // Closes the request class.
