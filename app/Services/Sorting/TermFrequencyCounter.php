<?php

// Inicia el archivo fuente de PHP.

namespace App\Services\Sorting;

// Ubica el contador junto a los servicios de ordenamiento que comparten su entrada.

class TermFrequencyCounter
{
    // Cuenta las apariciones de cada término normalizado recibido.
    /** @param array<int, string> $terms
     * @return array<string, int>
     */
    public function count(array $terms): array
    {
        // Inicializa el mapa que relaciona cada término con su frecuencia.
        $frequencies = [];

        // Recorre una sola vez la lista de palabras sin ordenar.
        foreach ($terms as $term) {
            // Ignora cadenas vacías por seguridad si se invoca fuera del flujo habitual.
            if ($term === '') {
                // Evita contabilizar una cadena vacía como término.
                continue;
            }

            // Incrementa la frecuencia de la palabra actual.
            $frequencies[$term] = ($frequencies[$term] ?? 0) + 1;
        }

        // Devuelve el mapa de frecuencia compartido por los algoritmos.
        return $frequencies;
    }
}
