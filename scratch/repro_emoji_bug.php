<?php

require_once __DIR__ . '/../vendor/autoload.php';

class TestFormatter {
    use App\Traits\FormatsTextTrait;
}

$formatter = new TestFormatter();

$html = "
<ul>
    <li>1</li>
    <li>2</li>
    <li>3</li>
</ul>
<p>😀</p>
";

echo "--- CASE 1: With <p> around emoji ---\n";
echo $formatter->formatHtmlForWhatsapp($html) . "\n\n";

$html2 = "<ul>
    <li>1</li>
    <li>2</li>
    <li>3</li>
</ul>😀";

echo "--- CASE 2: Emoji right after </ul> ---\n";
echo $formatter->formatHtmlForWhatsapp($html2) . "\n\n";
