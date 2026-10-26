<?php

// Inicia el archivo fuente de PHP.

namespace App\Http\Controllers\TermRanking; // Ubica las acciones del ranking en su propio espacio de nombres.

use App\Http\Controllers\Controller; // Importa la clase base compartida por los controladores de Laravel.
use App\Http\Requests\TermRanking\TermRankingRequest; // Importa la validación de artículos y algoritmo.
use App\Services\Article\ArticleService; // Importa la consulta de artículos y preparación de abstracts.
use App\Services\Sorting\BucketSortService; // Importa la implementación medida de Bucket Sort.
use App\Services\Sorting\CountingSortService; // Importa la implementación medida de Counting Sort.
use App\Services\Sorting\RadixSortService; // Importa la implementación medida de Radix Sort.
use Inertia\Inertia; // Importa el renderizador de páginas del lado del servidor.
use Inertia\Response; // Importa el tipo de respuesta que recibe React.

class TermRankingController extends Controller // Coordina la selección de artículos y la vista de resultados.
{
    // Abre la clase del controlador.
    // Declara los servicios que usan las acciones del controlador.
    public function __construct(
        private readonly ArticleService $articles, // Inyecta la consulta y preparación de artículos.
        private readonly CountingSortService $countingSort, // Inyecta el algoritmo Counting Sort.
        private readonly RadixSortService $radixSort, // Inyecta el algoritmo Radix Sort.
        private readonly BucketSortService $bucketSort, // Inyecta el algoritmo Bucket Sort.
    ) { // Abre el constructor.
    } // Cierra el constructor.

    public function index(): Response // Muestra la página para seleccionar artículos y algoritmo.
    { // Abre la acción de selección.
        return Inertia::render('term-rankings/index', [ // Renderiza la página de selección mediante Inertia.
            'articles' => $this->articles->rankingArticles(), // Envía todos los artículos sin incluir sus abstracts.
        ]); // Completa la respuesta de la página de selección.
    } // Cierra la acción de selección.

    public function results(TermRankingRequest $request): Response // Valida la solicitud y muestra el ranking calculado.
    { // Abre la acción de resultados.
        $validated = $request->validated(); // Obtiene únicamente los parámetros que superaron la validación.
        $articleIds = array_map('intval', $validated['article_ids']); // Convierte los identificadores validados a enteros.
        $terms = $this->articles->prepareTermsForRanking($articleIds); // Consulta, une, normaliza y tokeniza abstracts antes de medir.
        $sortResult = match ($validated['algorithm']) { // Elige el algoritmo validado y le entrega la misma lista sin ordenar.
            'counting-sort' => $this->countingSort->rank($terms), // Ejecuta Counting Sort sobre las palabras preparadas.
            'radix-sort' => $this->radixSort->rank($terms), // Ejecuta Radix Sort sobre las mismas palabras.
            'bucket-sort' => $this->bucketSort->rank($terms), // Ejecuta Bucket Sort sobre las mismas palabras.
        }; // Finaliza la selección y obtiene ranking y duración.

        return Inertia::render('term-rankings/results', [ // Renderiza la nueva página de resultados.
            'ranking' => $sortResult['ranking'], // Envía términos por frecuencia descendente y desempate alfabético.
            'algorithm' => $validated['algorithm'], // Identifica el algoritmo seleccionado para mostrarlo en el resultado.
            'executionTimeNanoseconds' => $sortResult['elapsedTimeNanoseconds'], // Envía la duración exclusiva del algoritmo.
            'articles' => $this->articles->rankingArticlesByIds($articleIds), // Envía los nombres de los artículos analizados.
        ]); // Completa la respuesta de la página de resultados.
    } // Cierra la acción de resultados.
} // Cierra la clase del controlador.
