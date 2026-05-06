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
        
        // Agregar un solo salto de línea antes de bloques para que no se peguen, pero evitar duplicar al cerrar
        $text = preg_replace('/<(?:p|div|section|article)[^>]*>/i', "\n", $text);
        $text = preg_replace('/<\/(?:p|div|section|article)>/i', "", $text);

        // 3. Manejar listas de forma más inteligente para mantener números/viñetas
        // Procesamos listas ordenadas (<ol>) para poner números
        $text = preg_replace_callback('/<ol[^>]*>(.*?)<\/ol>/is', function($matches) {
            // Limpiar saltos de línea del HTML original para evitar espacios dobles
            $items = str_replace(["\r", "\n"], "", $matches[1]);
            $count = 1;
            $res = preg_replace_callback('/<li[^>]*>(.*?)<\/li>/is', function($itemMatches) use (&$count) {
                return "\n" . ($count++) . ". " . trim(strip_tags($itemMatches[1]));
            }, $items);
            return "\n" . trim($res) . "\n";
        }, $text);

        // Procesamos listas no ordenadas (<ul>) para poner viñetas elegantes (•)
        $text = preg_replace_callback('/<ul[^>]*>(.*?)<\/ul>/is', function($matches) {
            // Limpiar saltos de línea del HTML original para evitar espacios dobles
            $items = str_replace(["\r", "\n"], "", $matches[1]);
            $res = preg_replace_callback('/<li[^>]*>(.*?)<\/li>/is', function($itemMatches) {
                return "\n• " . trim(strip_tags($itemMatches[1]));
            }, $items);
            return "\n" . trim($res) . "\n";
        }, $text);

        // Limpieza de cualquier li que haya quedado suelto (fuera de ul/ol)
        $text = preg_replace('/<li[^>]*>\s*/i', "\n• ", $text);
        $text = str_replace('</li>', "", $text);

        // Asegurar que ul/ol no queden con etiquetas sueltas si falló el callback
        $text = preg_replace('/<\/?(?:ul|ol)[^>]*>/i', "\n", $text);

        // 4. Formateo de WhatsApp: Negrita (*), Cursiva (_), Tachado (~)
        // Procesar cada línea por separado para evitar romper el formato si el usuario selecciona múltiples líneas
        $formatMultiline = function($content, $char) {
            $content = strip_tags($content);
            $lines = explode("\n", $content);
            $res = [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') {
                    $res[] = "{$char}{$trimmed}{$char}";
                } else {
                    $res[] = '';
                }
            }
            return " " . implode("\n", $res) . " ";
        };

        $text = preg_replace_callback('/<(?:b|strong)\b[^>]*>(.*?)<\/(?:b|strong)>/is', function($matches) use ($formatMultiline) {
            return $formatMultiline($matches[1], '*');
        }, $text);

        $text = preg_replace_callback('/<(?:i|em)\b[^>]*>(.*?)<\/(?:i|em)>/is', function($matches) use ($formatMultiline) {
            return $formatMultiline($matches[1], '_');
        }, $text);

        $text = preg_replace_callback('/<(?:s|strike|del)\b[^>]*>(.*?)<\/(?:s|strike|del)>/is', function($matches) use ($formatMultiline) {
            return $formatMultiline($matches[1], '~');
        }, $text);

        // 5. Limpiar cualquier otra etiqueta HTML restante
        $text = strip_tags($text);

        // 6. Normalización final de saltos de línea y espacios
        // Colapsar múltiples espacios (pero no saltos de línea)
        $text = preg_replace('/[ \t]+/', ' ', $text);
        
        $lines = explode("\n", $text);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $cleanLines[] = $trimmed;
            }
        }
        
        $text = implode("\n", $cleanLines);
        // Colapsar múltiples saltos de línea a uno solo por defecto para estilo compacto
        // Si el usuario puso un doble espacio real en el HTML, p. ej. con <br><br>, lo respetamos después de esto?
        // No, el usuario quiere "sin espacios".
        $text = preg_replace('/\n{2,}/', "\n", $text);
        $text = trim($text);

        return $text;
    }
}
