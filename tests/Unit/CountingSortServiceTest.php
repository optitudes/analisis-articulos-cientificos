<?php

// Inicia el archivo fuente de PHP.

namespace Tests\Unit; // Ubica la prueba en el espacio de nombres de pruebas unitarias.

use App\Services\Sorting\CountingSortService; // Importa el servicio que se probará.
use Tests\TestCase; // Importa la clase base de pruebas de la aplicación.

class CountingSortServiceTest extends TestCase // Agrupa pruebas específicas para Counting Sort.
{
    // Abre la clase de pruebas.
    public function test_it_ranks_by_frequency_and_breaks_ties_alphabetically(): void // Verifica frecuencia descendente y desempate determinista.
    { // Abre la prueba del comportamiento del ranking.
        $result = app(CountingSortService::class)->rank(['pera', 'manzana', 'uva', 'manzana', 'pera', 'banana', 'uva', 'pera']); // Ejecuta el algoritmo con términos desordenados.

        $this->assertSame([ // Compara cada fila calculada con el ranking esperado.
            ['term' => 'pera', 'frequency' => 3], // Espera primero el término más frecuente.
            ['term' => 'manzana', 'frequency' => 2], // Espera primero alfabéticamente entre los términos empatados.
            ['term' => 'uva', 'frequency' => 2], // Espera después el siguiente término del empate.
            ['term' => 'banana', 'frequency' => 1], // Espera al final el término menos frecuente.
        ], $result['ranking']); // Comprueba las filas devueltas por el algoritmo.
        $this->assertGreaterThanOrEqual(0, $result['elapsedTimeNanoseconds']); // Comprueba que el tiempo medido no sea negativo.
    } // Cierra la prueba del comportamiento del ranking.

    public function test_it_returns_an_empty_ranking_for_an_empty_input(): void // Verifica el caso límite de una entrada vacía.
    { // Abre la prueba de entrada vacía.
        $result = app(CountingSortService::class)->rank([]); // Ejecuta el algoritmo sin términos.

        $this->assertSame([], $result['ranking']); // Confirma que una entrada vacía no produzca filas.
        $this->assertGreaterThanOrEqual(0, $result['elapsedTimeNanoseconds']); // Confirma que también se mida el caso vacío.
    } // Cierra la prueba de entrada vacía.
} // Cierra la clase de pruebas.
