<?php

// Inicia el archivo fuente de PHP.

namespace App\Http\Requests\TermRanking; // Agrupa la validación de la función de ranking de términos.

use Illuminate\Foundation\Http\FormRequest; // Importa la clase base de validación de solicitudes de Laravel.
use Illuminate\Validation\Rule; // Importa reglas para limitar los algoritmos aceptados.

class TermRankingRequest extends FormRequest // Valida los parámetros de la solicitud de ranking.
{
    // Abre la clase de validación.
    public function authorize(): bool // Permite la solicitud cuando el middleware de autenticación ya autorizó el acceso.
    { // Abre el método de autorización.
        return true; // Delega la autenticación al middleware de la ruta.
    } // Cierra el método de autorización.

    /** @return array<string, array<int, mixed>> */ // Documenta la estructura de las reglas de validación.
    public function rules(): array // Declara los identificadores y algoritmos aceptados.
    { // Abre el método que define las reglas de validación.
        return [ // Devuelve las reglas para la solicitud de ranking.
            'article_ids' => ['required', 'array', 'min:2'], // Exige al menos dos identificadores de artículos.
            'article_ids.*' => ['required', 'integer', 'distinct', 'exists:articles,id'], // Exige identificadores existentes y sin duplicados.
            'algorithm' => ['required', 'string', Rule::in(['counting-sort', 'radix-sort', 'bucket-sort'])], // Acepta solo los tres algoritmos implementados.
        ]; // Cierra el arreglo de reglas de validación.
    } // Cierra el método de reglas.
} // Cierra la clase de solicitud.
