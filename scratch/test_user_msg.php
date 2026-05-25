<?php

require_once __DIR__ . '/../vendor/autoload.php';

class TestFormatter {
    use App\Traits\FormatsTextTrait;
}

$formatter = new TestFormatter();

// Simulating different possible formats the HTML could be stored as in the database:
// 1. Double newlines (plaintext)
$text1 = "📢 ¡Hola! 👋✨ Gracias por interesarte en nuestros Cubos LED

🔹 Características: Iluminación LED multicolor, control remoto, batería recargable y diseño moderno ideal para eventos, terrazas, jardines o negocios. 

💬 Me encantaría saber: 
¿Estás buscando darle un estilo más moderno y llamativo a tu espacio? 
Puedo ayudarte con el precio, cotización o cualquier duda que tengas 👍 
¡Estoy aquí para asesorarte sin compromiso!

✨ Crea ambientes únicos y haz que tu espacio destaque al instante. 
¿Quieres que te pase precio y disponibilidad ahora mismo? 👀 
Responde “QUIERO INFO” y lo vemos al toque 🚀 
Tami Maquinarias ✨";

// 2. HTML standard paragraphs with non-breaking spaces or empty paragraphs
$html2 = "<p>📢 ¡Hola! 👋✨ Gracias por interesarte en nuestros Cubos LED</p><p>&nbsp;</p><p>🔹 Características: Iluminación LED multicolor, control remoto, batería recargable y diseño moderno ideal para eventos, terrazas, jardines o negocios. </p><p>&nbsp;</p><p>💬 Me encantaría saber: <br>¿Estás buscando darle un estilo más moderno y llamativo a tu espacio? <br>Puedo ayudarte con el precio, cotización o cualquier duda que tengas 👍 <br>¡Estoy aquí para asesorarte sin compromiso!</p><p>&nbsp;</p><p>✨ Crea ambientes únicos y haz que tu espacio destaque al instante. <br>¿Quieres que te pase precio y disponibilidad ahora mismo? 👀 <br>Responde “QUIERO INFO” y lo vemos al toque 🚀 <br>Tami Maquinarias ✨</p>";

// 3. HTML with double <br>
$html3 = "📢 ¡Hola! 👋✨ Gracias por interesarte en nuestros Cubos LED<br><br>🔹 Características: Iluminación LED multicolor, control remoto, batería recargable y diseño moderno ideal para eventos, terrazas, jardines o negocios. <br><br>💬 Me encantaría saber: <br>¿Estás buscando darle un estilo más moderno y llamativo a tu espacio? <br>Puedo ayudarte con el precio, cotización o cualquier duda que tengas 👍 <br>¡Estoy aquí para asesorarte sin compromiso!<br><br>✨ Crea ambientes únicos y haz que tu espacio destaque al instante. <br>¿Quieres que te pase precio y disponibilidad ahora mismo? 👀 <br>Responde “QUIERO INFO” y lo vemos al toque 🚀 <br>Tami Maquinarias ✨";

echo "--- RUNNING TEST 1 (PLAIN TEXT WITH NEWLINES) ---\n";
echo "RESULT:\n" . $formatter->formatHtmlForWhatsapp($text1) . "\n\n";

echo "--- RUNNING TEST 2 (HTML WITH PARAGRAPHS & &nbsp;) ---\n";
echo "RESULT:\n" . $formatter->formatHtmlForWhatsapp($html2) . "\n\n";

echo "--- RUNNING TEST 3 (HTML WITH DOUBLE BR) ---\n";
echo "RESULT:\n" . $formatter->formatHtmlForWhatsapp($html3) . "\n\n";
