<?php

namespace App\Traits;

trait FormatsTextTrait
{
    /**
     * Convierte HTML básico a formato Markdown de WhatsApp.
     * Soporta negrita, cursiva, tachado, listas y saltos de línea.
     *
     * @param string|null $html
     * @return string
     */
    public function formatHtmlForWhatsapp(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // 1. Limpieza inicial y normalización de espacios
        $text = str_replace('&nbsp;', ' ', $html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 2. Manejar saltos de línea y bloques
        $text = str_replace(['<br>', '<br/>', '<br />'], "\n", $text);
        
        // Agregar saltos de línea antes y después de bloques para que no se peguen
        $text = preg_replace('/<(?:p|div|section|article)[^>]*>/i', "\n", $text);
        $text = preg_replace('/<\/(?:p|div|section|article)>/i', "\n", $text);

        // 3. Manejar listas de forma más inteligente para mantener números/viñetas
        // Procesamos listas ordenadas (<ol>) para poner números
        $text = preg_replace_callback('/<ol[^>]*>(.*?)<\/ol>/is', function($matches) {
            $items = $matches[1];
            $count = 1;
            return preg_replace_callback('/<li[^>]*>(.*?)<\/li>/is', function($itemMatches) use (&$count) {
                return "\n" . ($count++) . ". " . trim(strip_tags($itemMatches[1]));
            }, $items);
        }, $text);

        // Procesamos listas no ordenadas (<ul>) para poner viñetas elegantes (•)
        $text = preg_replace_callback('/<ul[^>]*>(.*?)<\/ul>/is', function($matches) {
            $items = $matches[1];
            return preg_replace_callback('/<li[^>]*>(.*?)<\/li>/is', function($itemMatches) {
                return "\n• " . trim(strip_tags($itemMatches[1]));
            }, $items);
        }, $text);

        // Limpieza de cualquier li que haya quedado suelto (fuera de ul/ol)
        $text = preg_replace('/<li[^>]*>\s*/i', "\n• ", $text);
        $text = str_replace('</li>', "", $text);

        // 4. Formateo de WhatsApp: Negrita (*), Cursiva (_), Tachado (~)
        $text = preg_replace_callback('/<(?:b|strong)[^>]*>(.*?)<\/(?:b|strong)>/is', function($matches) {
            $content = trim($matches[1]);
            return empty($content) ? '' : "*{$content}*";
        }, $text);

        $text = preg_replace_callback('/<(?:i|em)[^>]*>(.*?)<\/(?:i|em)>/is', function($matches) {
            $content = trim($matches[1]);
            return empty($content) ? '' : "_{$content}_";
        }, $text);

        $text = preg_replace_callback('/<(?:s|strike|del)[^>]*>(.*?)<\/(?:s|strike|del)>/is', function($matches) {
            $content = trim($matches[1]);
            return empty($content) ? '' : "~{$content}~";
        }, $text);

        // 5. Limpiar cualquier otra etiqueta HTML restante
        $text = strip_tags($text);

        // 6. Normalización final de saltos de línea y espacios
        $lines = explode("\n", $text);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (!empty($trimmed)) {
                $cleanLines[] = $trimmed;
            } else if (!empty($cleanLines) && end($cleanLines) !== "") {
                // Permitir un solo salto de línea vacío para separar párrafos
                $cleanLines[] = "";
            }
        }
        
        $text = implode("\n", $cleanLines);
        $text = trim($text);

        return $text;
    }
}
