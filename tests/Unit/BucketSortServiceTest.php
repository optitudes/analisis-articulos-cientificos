<?php

// Inicia el archivo fuente de PHP.

namespace Tests\Unit;

// Ubica esta clase en el espacio de nombres de pruebas unitarias.

use App\Services\Sorting\BucketSortService;
use Tests\TestCase;

// Importa el servicio Bucket Sort y la clase base para probarlo.

class BucketSortServiceTest extends TestCase
{
    // Agrupa las pruebas del algoritmo Bucket Sort.

    public function test_it_sorts_unsorted_terms_by_frequency_and_alphabetical_ties(): void
    {
        // Ejecuta Bucket Sort con términos deliberadamente desordenados.
        $result = app(BucketSortService::class)->rank(['zorro', 'alfa', 'beta', 'alfa', 'zorro', 'beta', 'zorro', 'gama']);

        // Comprueba que la frecuencia descendente y el desempate alfabético sean correctos.
        $this->assertSame([
            ['term' => 'zorro', 'frequency' => 3],
            ['term' => 'alfa', 'frequency' => 2],
            ['term' => 'beta', 'frequency' => 2],
            ['term' => 'gama', 'frequency' => 1],
        ], $result['ranking']);

        // Confirma que el servicio informe el tiempo medido en nanosegundos.
        $this->assertGreaterThanOrEqual(0, $result['elapsedTimeNanoseconds']);
    }

    public function test_it_returns_an_empty_ranking_when_no_terms_are_provided(): void
    {
        // Ejecuta Bucket Sort sin términos de entrada.
        $result = app(BucketSortService::class)->rank([]);

        // Confirma que la lista de resultados esté vacía.
        $this->assertSame([], $result['ranking']);
    }

    public function test_it_orders_terms_across_distinct_frequency_ranges(): void
    {
        // Crea frecuencias que se distribuyen en varios rangos de buckets.
        $terms = array_merge(
            array_fill(0, 12, 'mayor'),
            array_fill(0, 10, 'zeta'),
            array_fill(0, 10, 'alfa'),
            array_fill(0, 9, 'menor'),
        );

        // Ejecuta Bucket Sort con frecuencias en distintos intervalos.
        $result = app(BucketSortService::class)->rank($terms);

        // Comprueba el orden descendente y el desempate alfabético entre frecuencias iguales.
        $this->assertSame([
            ['term' => 'mayor', 'frequency' => 12],
            ['term' => 'alfa', 'frequency' => 10],
            ['term' => 'zeta', 'frequency' => 10],
            ['term' => 'menor', 'frequency' => 9],
        ], $result['ranking']);
    }
}
