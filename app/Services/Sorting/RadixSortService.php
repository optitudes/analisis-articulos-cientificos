<?php

// Inicia el archivo fuente de PHP.

namespace App\Services\Sorting;

// Ubica Radix Sort junto a los demás algoritmos de ordenamiento.

class RadixSortService
{
    // Inyecta el contador de frecuencias compartido por los algoritmos.
    public function __construct(private readonly TermFrequencyCounter $frequencyCounter)
    {
        // No requiere inicialización adicional porque la dependencia ya está inyectada.
    }

    // Ordena las frecuencias mediante pasadas estables por cada dígito decimal.
    /** @param array<int, string> $terms
     * @return array{ranking: array<int, array{term: string, frequency: int}>, elapsedTimeNanoseconds: int}
     */
    public function rank(array $terms): array
    {
        // Inicia el cronómetro después del procesamiento de abstracts y antes de contar las palabras.
        $startedAt = hrtime(true);

        // Calcula dentro de la medición cuántas veces aparece cada palabra.
        $frequencies = $this->frequencyCounter->count($terms);

        // Convierte el mapa de frecuencias en elementos ordenables por una clave numérica.
        $items = $this->createItems($frequencies);

        // Ordena de forma ascendente las frecuencias usando Radix Sort estable.
        $sortedItems = $this->sortByFrequency($items);

        // Invierte grupos de frecuencia sin alterar el desempate alfabético requerido.
        $ranking = $this->collectDescendingRanking($sortedItems);

        // Devuelve los resultados junto con el tiempo medido de todo el algoritmo.
        return [
            // Proporciona las filas de términos y frecuencias para la vista.
            'ranking' => $ranking,

            // Detiene el cronómetro y reporta nanosegundos.
            'elapsedTimeNanoseconds' => hrtime(true) - $startedAt,
        ];
    }

    // Transforma el mapa término-frecuencia en registros ordenables.
    /** @param array<string, int> $frequencies
     * @return array<int, array{term: string, frequency: int}>
     */
    private function createItems(array $frequencies): array
    {
        // Inicializa los registros que Radix Sort procesará.
        $items = [];

        // Recorre cada término único y el número de sus apariciones.
        foreach ($frequencies as $term => $frequency) {
            // Guarda la palabra y usa su frecuencia positiva como clave de ordenamiento.
            $items[] = ['term' => $term, 'frequency' => $frequency];
        }

        // Devuelve los registros sin ordenamiento alfabético previo.
        return $items;
    }

    // Aplica Radix Sort LSD estable sobre las frecuencias enteras no negativas.
    /** @param array<int, array{term: string, frequency: int}> $items
     * @return array<int, array{term: string, frequency: int}>
     */
    private function sortByFrequency(array $items): array
    {
        // Devuelve de inmediato cuando no hay términos que ordenar.
        if ($items === []) {
            // Conserva el mismo formato de lista para una entrada vacía.
            return [];
        }

        // Obtiene la frecuencia máxima para determinar cuántas pasadas son necesarias.
        $remainingMaximum = max(array_column($items, 'frequency'));

        // Comienza por la posición de las unidades.
        $placeValue = 1;

        // Procesa un dígito decimal por pasada hasta llegar al dígito más significativo.
        do {
            // Reserva diez buckets, uno por cada dígito decimal posible.
            $digitBuckets = array_fill(0, 10, []);

            // Distribuye los registros por el dígito de la posición actual.
            foreach ($items as $item) {
                // Extrae el dígito de la frecuencia en la posición actual.
                $digit = intdiv($item['frequency'], $placeValue) % 10;

                // Agrega el registro al bucket de su dígito sin cambiar su orden relativo.
                $digitBuckets[$digit][] = $item;
            }

            // Reinicia la lista que recibirá los buckets en orden ascendente.
            $items = [];

            // Recorre los dígitos de cero a nueve para mantener estabilidad.
            foreach ($digitBuckets as $bucket) {
                // Anexa los registros del bucket preservando el orden de entrada.
                foreach ($bucket as $item) {
                    // Conserva el término asociado a su frecuencia durante la pasada.
                    $items[] = $item;
                }
            }

            // Avanza la frecuencia máxima a su siguiente dígito decimal.
            $remainingMaximum = intdiv($remainingMaximum, 10);

            // Aumenta el valor posicional solo si queda otra pasada por realizar.
            if ($remainingMaximum > 0) {
                // Prepara la siguiente potencia de diez para extraer el próximo dígito.
                $placeValue *= 10;
            }
        } while ($remainingMaximum > 0);

        // Devuelve los registros en orden ascendente de frecuencia.
        return $items;
    }

    // Lee las frecuencias descendentes y ordena alfabéticamente solo los empates.
    /** @param array<int, array{term: string, frequency: int}> $items
     * @return array<int, array{term: string, frequency: int}>
     */
    private function collectDescendingRanking(array $items): array
    {
        // Inicializa la lista final en orden descendente.
        $ranking = [];

        // Comienza en el extremo de mayor frecuencia del resultado ascendente.
        $index = count($items) - 1;

        // Recorre los registros hasta consumir todos los grupos de frecuencia.
        while ($index >= 0) {
            // Identifica la frecuencia del grupo actual.
            $frequency = $items[$index]['frequency'];

            // Inicializa el conjunto de términos empatados en esta frecuencia.
            $tiedTerms = [];

            // Reúne hacia atrás todos los registros con la misma frecuencia.
            while ($index >= 0 && $items[$index]['frequency'] === $frequency) {
                // Añade el término del registro actual al grupo de empate.
                $tiedTerms[] = $items[$index]['term'];

                // Avanza hacia la frecuencia inmediatamente inferior.
                $index--;
            }

            // Ordena alfabéticamente los términos que comparten frecuencia.
            sort($tiedTerms, SORT_STRING);

            // Agrega el grupo empatado a la salida con la frecuencia común.
            foreach ($tiedTerms as $term) {
                // Añade una fila de resultado para el término actual.
                $ranking[] = ['term' => $term, 'frequency' => $frequency];
            }
        }

        // Devuelve el ranking descendente con empates alfabéticos.
        return $ranking;
    }
}
