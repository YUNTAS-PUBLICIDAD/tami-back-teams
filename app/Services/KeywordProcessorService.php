<?php

namespace App\Services;

class KeywordProcessorService
{
    /**
     * Procesa keywords desde JSON y las convierte a string separado por comas.
     *
     * @param string|null $keywordsJson JSON array de keywords o null
     * @return string|null Keywords concatenadas separadas por comas
     */
    public function processKeywordsFromJson(?string $keywordsJson): ?string
    {
        if (empty($keywordsJson)) {
            return null;
        }

        $keywords = json_decode($keywordsJson, true);

        if (!is_array($keywords) || empty($keywords)) {
            return null;
        }

        $cleaned = array_filter(array_map('trim', $keywords));
        
        return implode(', ', $cleaned);
    }

    /**
     * Extrae keywords de un string separado por comas y devuelve un array limpio.
     *
     * @param string $keywords String de keywords separadas por comas
     * @return array Array de keywords sin espacios ni valores vacíos
     */
    public function extractFromString(string $keywords): array
    {
        return array_filter(array_map('trim', explode(',', $keywords)));
    }

    /**
     * Convierte un array de keywords a JSON.
     *
     * @param array $keywords Array de keywords
     * @return string JSON string
     */
    public function toJson(array $keywords): string
    {
        return json_encode($keywords);
    }
}
