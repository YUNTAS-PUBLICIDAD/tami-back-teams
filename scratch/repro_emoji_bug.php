<?php

require_once __DIR__ . '/../vendor/autoload.php';

class TestFormatter {
    use App\Traits\FormatsTextTrait;
}

$formatter = new TestFormatter();

// 🛠️ is encoded in UTF-8 as \xF0\x9F\x9B\xA0
// Note the ending \xA0 byte.
$emoji = "🛠️";
$text = "Estructura de acero inoxidable 304 " . $emoji;

echo "Original text: " . $text . "\n";
echo "Original bytes in hex: " . bin2hex($text) . "\n\n";

$formatted = $formatter->formatHtmlForWhatsapp($text);
echo "Formatted text: " . $formatted . "\n";
echo "Formatted bytes in hex: " . bin2hex($formatted) . "\n\n";

// Let's test if json_encode fails on the formatted string
$json = json_encode(['message' => $formatted]);
if ($json === false) {
    echo "json_encode failed: " . json_last_error_msg() . "\n";
} else {
    echo "json_encode succeeded!\n";
}
