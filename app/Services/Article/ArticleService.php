<?php

namespace App\Services\Article;

use App\Models\Article\Article;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str; // Importa la utilidad de transliteración de Laravel para normalizar texto acentuado.

class ArticleService
{
    /** @return Collection<int, Article> */
    public function all(): Collection
    {
        return Article::query()->orderBy('name')->get();
    }

    /** Devuelve los datos mínimos necesarios para elegir artículos en el ranking. */
    public function rankingArticles(): Collection // Expone los artículos sin cargar sus abstracts completos.
    { // Inicia la consulta de artículos disponibles.
        return Article::query() // Construye una consulta Eloquent para los artículos científicos.
            ->select(['id', 'name']) // Limita la respuesta al identificador y al nombre visible.
            ->orderBy('name') // Presenta las opciones en un orden de nombres predecible.
            ->get(); // Ejecuta la consulta y devuelve todos los artículos disponibles.
    } // Finaliza la consulta de artículos disponibles.

    /** Devuelve los nombres de los artículos seleccionados para la vista de resultados. */
    public function rankingArticlesByIds(array $articleIds): Collection // Carga los datos visibles de los artículos seleccionados.
    { // Inicia la consulta de artículos seleccionados.
        return Article::query() // Construye una consulta Eloquent para los artículos del ranking.
            ->select(['id', 'name']) // Evita volver a cargar los abstracts.
            ->whereIn('id', $articleIds) // Limita la consulta a los identificadores ya validados.
            ->orderBy('name') // Mantiene predecible el orden de los nombres seleccionados.
            ->get(); // Ejecuta la consulta y devuelve los nombres seleccionados.
    } // Finaliza la consulta de artículos seleccionados.

    /** @param array<int, int> $articleIds */
    public function prepareTermsForRanking(array $articleIds): array // Coordina la preparación del texto antes de medir el ordenamiento.
    { // Inicia el flujo de preparación de palabras.
        $abstracts = $this->abstractsForArticles($articleIds); // Lee todos los abstracts seleccionados con una sola consulta.
        $combinedAbstract = $this->combineAbstracts($abstracts); // Une los abstracts en un único texto.
        $sanitizedAbstract = $this->sanitizeAbstract($combinedAbstract); // Normaliza mayúsculas y tildes antes de separar palabras.

        return $this->splitAbstractIntoTerms($sanitizedAbstract); // Extrae palabras sin ordenarlas alfabéticamente.
    } // Finaliza el flujo de preparación de palabras.

    /** @param array<int, int> $articleIds
     * @return array<int, string|null>
     */
    private function abstractsForArticles(array $articleIds): array // Obtiene juntos los abstracts de todos los artículos seleccionados.
    { // Inicia la recuperación de abstracts con una sola consulta.
        return Article::query() // Construye una consulta Eloquent que solo necesita los abstracts.
            ->whereIn('id', $articleIds) // Selecciona los artículos elegidos por el usuario.
            ->orderBy('id') // Hace determinista la concatenación sin depender del plan de consulta.
            ->pluck('abstract') // Recupera únicamente la columna abstract de cada artículo.
            ->all(); // Convierte la colección en un arreglo PHP.
    } // Finaliza la recuperación de abstracts con una sola consulta.

    /** @param array<int, string|null> $abstracts */
    private function combineAbstracts(array $abstracts): string // Produce el texto único que necesita el tokenizador.
    { // Inicia la concatenación de abstracts.
        $availableAbstracts = array_filter( // Elimina abstracts vacíos sin modificar los que contienen texto.
            $abstracts, // Utiliza los abstracts recuperados en una sola consulta.
            static fn (?string $abstract): bool => $abstract !== null && trim($abstract) !== '', // Omite resúmenes nulos o en blanco.
        ); // Finaliza el filtrado de abstracts sin contenido utilizable.

        return implode("\n", $availableAbstracts); // Une todos los abstracts útiles en un texto grande.
    } // Finaliza la concatenación de abstracts.

    private function sanitizeAbstract(string $abstract): string // Normaliza mayúsculas y elimina tildes antes de separar.
    { // Inicia la normalización del texto.
        $lowercaseAbstract = mb_strtolower($abstract, 'UTF-8'); // Convierte las letras Unicode a minúsculas de forma consistente.

        return Str::ascii($lowercaseAbstract); // Translitera letras acentuadas como á y ñ a letras ASCII simples.
    } // Finaliza la normalización del texto.

    /** @return array<int, string> */
    private function splitAbstractIntoTerms(string $abstract): array // Extrae términos alfabéticos y descarta puntuación.
    { // Inicia la tokenización basada en una expresión regular.
        $terms = preg_split( // Divide el abstract normalizado en palabras.
            '/[^a-z]+/u', // Usa cada secuencia de caracteres que no sea una letra a-z como separador; así descarta puntuación, números y espacios.
            $abstract, // Utiliza el texto en minúsculas y sin tildes producido por sanitizeAbstract.
            -1, // Aplica la expresión a todo el abstract sin limitar la cantidad de divisiones.
            PREG_SPLIT_NO_EMPTY, // Evita generar términos vacíos por puntuación o espacios repetidos.
        ); // Finaliza la división compatible con Unicode.

        return $terms === false ? [] : array_values($terms); // Devuelve una lista reindexada y controla posibles fallos de la expresión.
    } // Finaliza la tokenización basada en una expresión regular.

    public function findOrFail(int $id): Article
    {
        return Article::query()->findOrFail($id);
    }

    /** @param array{name: string, abstract?: string|null, relative_path: string} $data */
    public function update(int $id, array $data): Article
    {
        $article = $this->findOrFail($id);
        $article->update($data);

        return $article->refresh();
    }

    public function delete(int $id): void
    {
        $this->findOrFail($id)->delete();
    }
}
