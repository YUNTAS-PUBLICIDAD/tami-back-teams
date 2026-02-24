<?php

namespace App\Traits;

trait SanitizesInput
{
    /**
     * Sanitiza campos de texto libre eliminando HTML y caracteres peligrosos
     */
    private function sanitizeText(?string $text): ?string
    {
        if (!$text) return $text;

        // Eliminar tags HTML
        $text = $this->removeAllHtmlTags($text);
        $text = $this->removeHtmlAttributes($text);

        // Convertir caracteres especiales
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        
        // Eliminar caracteres de control
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Limpiar espacios
        $text = trim($text);
        
        return $text;
    }

    /**
     * Elimina TODOS los tags HTML, incluso malformados
     */
    private function removeAllHtmlTags(string $text): string
    {
        // Eliminar tags HTML normales
        $text = strip_tags($text);
        
        // Eliminar cualquier cosa que parezca un tag (incluso malformados)
        $text = preg_replace('/<[^>]*>?/', '', $text);
        
        // Eliminar tags que no se cerraron completamente
        $text = preg_replace('/<[^<]*$/', '', $text);
        
        return $text;
    }

    /**
     * Elimina atributos HTML remanentes (onerror=, onclick=, etc.)
     */
    private function removeHtmlAttributes(string $text): string
    {
        // Lista de atributos peligrosos
        $dangerousAttributes = [
            'onerror', 'onclick', 'onload', 'onmouseover', 'onmouseout',
            'onfocus', 'onblur', 'onchange', 'onsubmit', 'onreset',
            'onkeydown', 'onkeyup', 'onkeypress', 'ondblclick',
            'oncontextmenu', 'onwheel', 'ondrag', 'ondrop',
            'style', 'class', 'id'
        ];
        
        // Eliminar cualquier atributo=valor
        foreach ($dangerousAttributes as $attr) {
            $text = preg_replace('/' . $attr . '\s*=\s*["\'][^"\']*["\']?/i', '', $text);
            $text = preg_replace('/' . $attr . '\s*=\s*[^"\'\s>]+/i', '', $text);
        }
        
        // Eliminar cualquier patrón on*= restante
        $text = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']?/i', '', $text);
        
        // Eliminar src= que quede suelto
        $text = preg_replace('/\s*src\s*=\s*["\'][^"\']*["\']?/i', '', $text);
        
        return $text;
    }

    /**
     * Sanitiza contenido HTML permitiendo solo tags básicos seguros
     */
    private function sanitizeHtml(?string $html): ?string
    {
        if (!$html) return $html;

        // Eliminar atributos peligrosos
        $html = $this->removeHtmlAttributes($html);

        // Lista de tags HTML permitidos (básicos para descripción)
        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><h3><h4>';
        
        // Eliminar tags no permitidos
        $html = strip_tags($html, $allowedTags);
        
        // Segunda pasada: eliminar cualquier atributo remanente
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);
        $html = preg_replace('/data\s*:/i', '', $html);
        $html = preg_replace('/\s*style\s*=\s*["\'][^"\']*["\']/', '', $html);
        
        // 5. Validar que no queden caracteres sospechosos
        if (preg_match('/[<>"\'].*(?:onerror|onclick|javascript|data:)/i', $html)) {
            return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
        }
        
        return trim($html);
    }

    /**
     * Sanitiza keywords manteniendo formato de array
     */
    private function sanitizeKeywords($keywords): ?array
    {
        if (!$keywords) return null;

        // Si es string, convertir a array
        if (is_string($keywords)) {
            $keywords = explode(',', $keywords);
        }

        if (!is_array($keywords)) {
            return null; // Si no es string ni array, retornar null
        }

        $cleanKeywords = [];
        
        foreach ($keywords as $keyword) {
            if (!is_string($keyword)) continue;
            
            // Convertir a texto plano
            $clean = strip_tags($keyword);
            $clean = trim(htmlspecialchars($clean, ENT_QUOTES, 'UTF-8'));
            
            if (!empty($clean) && strlen($clean) <= 50) { // Limitar longitud
                $cleanKeywords[] = $clean;
            }
        }
        
        return empty($cleanKeywords) ? null : $cleanKeywords;
    }

    /**
     * Valida lista blanca de valores permitidos
     */
    private function validateWhitelist(string $value, array $allowedValues, string $fieldName = 'campo'): string
    {
        if (!in_array($value, $allowedValues)) {
            throw new \InvalidArgumentException("Valor no permitido para {$fieldName}: {$value}");
        }
        
        return $value;
    }

    /**
     * Sanitiza arrays de strings
     */
    private function sanitizeArray(?array $items): ?array
    {
        if (!$items) return $items;
        
        $sanitized = [];
        foreach ($items as $key => $value) {
            // Sanitizar tanto la clave como el valor
            $cleanKey = is_string($key) ? $this->sanitizeText($key) : $key;
            $cleanValue = is_string($value) ? $this->sanitizeText($value) : $value;
            
            if (!empty($cleanValue)) {
                $sanitized[$cleanKey] = $cleanValue;
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitiza slug para URLs seguras
     */
    private function sanitizeSlug(?string $slug): ?string
    {
        if (!$slug) return $slug;

        // Convertir a lowercase
        $slug = strtolower($slug);
        
        // Solo permitir caracteres alfanuméricos y guiones
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        
        // Eliminar múltiples guiones consecutivos
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Eliminar guiones al inicio y final
        $slug = trim($slug, '-');
        
        // Validar longitud
        if (strlen($slug) > 160) {
            $slug = substr($slug, 0, 160);
        }
        
        return $slug;
    }

    /**
     * Sanitiza emails
     */
    private function sanitizeEmail(?string $email): ?string
    {
        if (!$email) return $email;

        // Filtrar y validar email
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        
        // Validar que sea un email válido
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email no válido.');
        }
        
        return strtolower($email);
    }

    /**
     * Sanitiza números de teléfono
     */
    private function sanitizePhone(?string $phone): ?string
    {
        if (!$phone) return $phone;

        // Eliminar todo excepto números, espacios, guiones, paréntesis y el símbolo +
        $phone = preg_replace('/[^0-9\s\-\(\)\+]/', '', $phone);
        
        // Limpiar espacios extra
        $phone = trim($phone);
        
        // Validar que contenga exactamente 9 dígitos
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($digits) !== 9) {
            throw new \InvalidArgumentException('El teléfono debe contener exactamente 9 dígitos.');
        }
        
        return $phone;
    }

    /**
     * Sanitiza nombres de archivos/slots (prevenir path traversal)
     */
    private function sanitizeFileName(?string $fileName): ?string
    {
        if (!$fileName) return $fileName;

        // Eliminar caracteres peligrosos para nombres de archivo
        $fileName = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $fileName);
        
        // Prevenir path traversal
        $fileName = str_replace(['../', '.\\', '..\\'], '', $fileName);
        
        // Eliminar puntos al inicio (archivos ocultos)
        $fileName = ltrim($fileName, '.');
        
        return $fileName;
    }

    /**
     * Sanitiza URLs
     */
    private function sanitizeUrl(?string $url): ?string
    {
        if (!$url) return $url;

        // Filtrar URL
        $url = filter_var(trim($url), FILTER_SANITIZE_URL);
        
        // Validar que sea una URL válida
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('URL no válida.');
        }
        
        // Solo permitir HTTP y HTTPS
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'])) {
            throw new \InvalidArgumentException('Solo se permiten URLs HTTP/HTTPS.');
        }
        
        return $url;
    }

    /**
     * Sanitiza números enteros
     */
    private function sanitizeInteger($value): ?int
    {
        if ($value === null) return null;
        
        $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        $value = filter_var($value, FILTER_VALIDATE_INT);
        
        if ($value === false) {
            throw new \InvalidArgumentException('Valor entero no válido.');
        }
        
        return $value;
    }

    /**
     * Sanitiza números decimales
     */
    private function sanitizeFloat($value): ?float
    {
        if ($value === null) return null;
        
        $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $value = filter_var($value, FILTER_VALIDATE_FLOAT);
        
        if ($value === false) {
            throw new \InvalidArgumentException('Valor decimal no válido.');
        }
        
        return $value;
    }
}