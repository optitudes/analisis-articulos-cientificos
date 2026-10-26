<?php

// Inicia el archivo fuente de PHP.

namespace App\Services\Sorting;

// Ubica Bucket Sort junto a los demás algoritmos de ordenamiento.

class BucketSortService
{
    // Inyecta el contador de frecuencias compartido por los algoritmos.
    public function __construct(private readonly TermFrequencyCounter $frequencyCounter)
    {
        // No requiere inicialización adicional porque la dependencia ya está inyectada.
    }

    // Distribuye las frecuencias en rangos y devuelve el ranking junto con el tiempo medido.
    /** @param array<int, string> $terms
     * @return array{ranking: array<int, array{term: string, frequency: int}>, elapsedTimeNanoseconds: int}
     */
    public function rank(array $terms): array
    {
        // Inicia la medición antes de contar las palabras y construir los buckets.
        $startedAt = hrtime(true);

        // Cuenta las apariciones dentro del tiempo atribuible al algoritmo.
        $frequencies = $this->frequencyCounter->count($terms);

        // Convierte cada término en un registro con su clave numérica de frecuencia.
        $items = $this->createItems($frequencies);

        // Distribuye y ordena los registros dentro de rangos de frecuencia.
        $buckets = $this->sortItemsIntoBuckets($items);

        // Concatena los rangos de mayor a menor para obtener el ranking.
        $ranking = $this->collectRanking($buckets);

        // Devuelve el ranking y el tiempo total empleado por el algoritmo.
        return [
            // Proporciona las filas de términos y frecuencias para la vista.
            'ranking' => $ranking,

            // Detiene el cronómetro y reporta nanosegundos.
            'elapsedTimeNanoseconds' => hrtime(true) - $startedAt,
        ];
    }

    // Convierte el mapa de frecuencias en registros que Bucket Sort pueda distribuir.
    /** @param array<string, int> $frequencies
     * @return array<int, array{term: string, frequency: int}>
     */
    private function createItems(array $frequencies): array
    {
        // Inicializa la lista de registros ordenables.
        $items = [];

        // Recorre cada término único con su frecuencia asociada.
        foreach ($frequencies as $term => $frequency) {
            // Conserva el término junto con su clave numérica.
            $items[] = ['term' => $term, 'frequency' => $frequency];
        }

        // Devuelve los registros sin exigir un orden de entrada.
        return $items;
    }

    // Asigna cada registro a un rango y ordena los elementos dentro de cada bucket.
    /** @param array<int, array{term: string, frequency: int}> $items
     * @return array<int, array<int, array{term: string, frequency: int}>>
     */
    private function sortItemsIntoBuckets(array $items): array
    {
        // Devuelve una estructura vacía cuando no hay palabras que clasificar.
        if ($items === []) {
            // Mantiene una lista de buckets válida para la etapa de salida.
            return [];
        }

        // Obtiene el valor máximo del rango de frecuencias positivas.
        $maximumFrequency = max(array_column($items, 'frequency'));

        // Elige aproximadamente la raíz cuadrada del máximo como cantidad de rangos.
        $bucketCount = max(1, (int) ceil(sqrt($maximumFrequency)));

        // Calcula el ancho entero de cada rango para cubrir del uno al máximo.
        $bucketWidth = max(1, (int) ceil($maximumFrequency / $bucketCount));

        // Reserva los buckets que cubrirán el rango de frecuencias.
        $buckets = array_fill(0, $bucketCount, []);

        // Distribuye cada registro según el rango al que pertenece su frecuencia.
        foreach ($items as $item) {
            // Convierte la frecuencia basada en uno a un índice basado en cero.
            $bucketIndex = intdiv($item['frequency'] - 1, $bucketWidth);

            // Inserta el registro en el bucket correspondiente a su intervalo.
            $buckets[$bucketIndex][] = $item;
        }

        // Ordena cada bucket por frecuencia descendente y término alfabético en empates.
        foreach ($buckets as &$bucket) {
            // Ordena los elementos locales al rango actual.
            usort($bucket, static function (array $left, array $right): int {
                // Prioriza la frecuencia más alta dentro del mismo rango.
                $frequencyComparison = $right['frequency'] <=> $left['frequency'];

                // Conserva el orden de frecuencia cuando los elementos no están empatados.
                if ($frequencyComparison !== 0) {
                    // Devuelve el resultado de la comparación numérica.
                    return $frequencyComparison;
                }

                // Desempata alfabéticamente cuando dos términos tienen igual frecuencia.
                return strcmp($left['term'], $right['term']);
            });
        }

        // Desvincula la referencia usada para modificar los buckets en el ciclo anterior.
        unset($bucket);

        // Devuelve los buckets ya ordenados internamente.
        return $buckets;
    }

    // Concatena los buckets desde el rango de mayor frecuencia hasta el menor.
    /** @param array<int, array<int, array{term: string, frequency: int}>> $buckets
     * @return array<int, array{term: string, frequency: int}>
     */
    private function collectRanking(array $buckets): array
    {
        // Inicializa la lista que se enviará a la vista de resultados.
        $ranking = [];

        // Recorre los buckets en orden inverso para priorizar las frecuencias altas.
        for ($bucketIndex = count($buckets) - 1; $bucketIndex >= 0; $bucketIndex--) {
            // Agrega en orden los elementos del bucket actual.
            foreach ($buckets[$bucketIndex] as $item) {
                // Añade el término y su frecuencia al ranking final.
                $ranking[] = $item;
            }
        }

        // Devuelve las filas ordenadas por frecuencia y desempate alfabético.
        return $ranking;
    }
}
