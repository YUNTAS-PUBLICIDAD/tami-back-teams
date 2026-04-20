<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Mock the trait since we can't easily bootstrap Laravel for a simple script
class TestFormatter {
    use App\Traits\FormatsTextTrait;
}

$formatter = new TestFormatter();

$html = "
<p>Hola <strong> Juan </strong>,</p>
<p>Esto es una prueba de <em> cursiva </em> y <s> tachado </s>.</p>
<ul>
    <li>Elemento 1</li>
    <li>Elemento 2</li>
</ul>
<p>Visita <a href='#'>nuestra web</a>.</p>
<br>
<p>¡Gracias!</p>
";

echo "--- ORIGINAL HTML ---\n";
echo $html . "\n";
echo "--- CONVERTED WHATSAPP (FIXED) ---\n";
echo $formatter->formatHtmlForWhatsapp($html) . "\n";
echo "-------------------------\n";

$html2 = "<b>  Negrita  </b> <i> Inclinada </i> <u> Subrayado </u> <s> Tachado </s>";
echo "Prueba con espacios: " . $formatter->formatHtmlForWhatsapp($html2) . "\n";

$html3 = "¡Hola! <b>Me</b> <s>gustaría</s> <s>obtener más</s> <i>información</i> <i>sobre la promoción.</i>";
echo "Caso similar a imagen: " . $formatter->formatHtmlForWhatsapp($html3) . "\n";

$html4 = "<ol><li>Paso uno</li><li>Paso dos</li></ol>";
echo "Prueba lista numerada: " . $formatter->formatHtmlForWhatsapp($html4) . "\n";
