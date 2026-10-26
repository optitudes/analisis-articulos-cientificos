<?php

// Inicia el archivo fuente de PHP.

namespace App\Services\Sorting;

// Ubica Counting Sort en el espacio de nombres de los servicios de ordenamiento.

class CountingSortService
{
    // Inyecta el contador común para que los algoritmos procesen el mismo tipo de entrada.
    public function __construct(private readonly TermFrequencyCounter $frequencyCounter)
    {
        // No requiere inicialización adicional porque la dependencia ya está inyectada.
    }

    // Recibe las palabras preparadas y devuelve el ranking junto con su tiempo medido.
    /** @param array<int, string> $terms
     * @return array{ranking: array<int, array{term: string, frequency: int}>, elapsedTimeNanoseconds: int}
     */
    public function rank(array $terms): array
    {
        // Inicia la medición después de que ArticleService terminó el procesamiento previo.
        $startedAt = hrtime(true);

        // Cuenta las palabras dentro del tiempo atribuible al algoritmo.
        $frequencies = $this->frequencyCounter->count($terms);

        // Ubica cada término en un bucket identificado por su frecuencia numérica.
        $buckets = $this->buildFrequencyBuckets($frequencies);

        // Recorre las frecuencias de mayor a menor y resuelve los empates alfabéticamente.
        $ranking = $this->collectRanking($buckets);

        // Devuelve el ranking junto con la duración total de los pasos del algoritmo.
        return [
            // Expone las filas listas para mostrar en la tabla de resultados.
            'ranking' => $ranking,

            // Detiene el cronómetro e informa el tiempo en nanosegundos.
            'elapsedTimeNanoseconds' => hrtime(true) - $startedAt,
        ];
    }

    // Agrupa los términos según el valor entero de su frecuencia.
    /** @param array<string, int> $frequencies
     * @return array<int, array<int, string>>
     */
    private function buildFrequencyBuckets(array $frequencies): array
    {
        // Obtiene la frecuencia máxima y contempla una entrada sin términos.
        $maximumFrequency = $frequencies === [] ? 0 : max($frequencies);

        // Reserva un índice para cada frecuencia posible, desde cero hasta el máximo.
        $buckets = array_fill(0, $maximumFrequency + 1, []);

        // Recorre cada término único y su frecuencia calculada.
        foreach ($frequencies as $term => $frequency) {
            // Inserta el término en el bucket correspondiente a su frecuencia.
            $buckets[$frequency][] = $term;
        }

        // Devuelve los buckets para construir el ranking descendente.
        return $buckets;
    }

    // Convierte los buckets en filas ordenadas por frecuencia y alfabéticamente en los empates.
    /** @param array<int, array<int, string>> $buckets
     * @return array<int, array{term: string, frequency: int}>
     */
    private function collectRanking(array $buckets): array
    {
        // Inicializa la lista final de términos y frecuencias.
        $ranking = [];

        // Recorre las frecuencias desde la mayor hasta la menor.
        for ($frequency = count($buckets) - 1; $frequency > 0; $frequency--) {
            // Obtiene los términos que comparten la frecuencia actual.
            $termsAtFrequency = $buckets[$frequency];

            // Ordena alfabéticamente solo los términos empatados en esta frecuencia.
            sort($termsAtFrequency, SORT_STRING);

            // Agrega cada término del bucket actual al ranking.
            foreach ($termsAtFrequency as $term) {
                // Añade una fila con el término y su número de apariciones.
                $ranking[] = [
                    // Guarda la palabra normalizada que se mostrará en la tabla.
                    'term' => $term,

                    // Guarda la frecuencia que determinó la posición del término.
                    'frequency' => $frequency,
                ];
            }
        }

        // Devuelve el ranking final en orden descendente de frecuencia.
        return $ranking;
    }
}
