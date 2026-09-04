<?php

namespace App\Console\Commands;

use App\Models\ProductoImagen;
use App\Models\ProductoWhatsappPaso;
use App\Models\ProductoEmailPaso;
use App\Services\ProductoImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RenameImagesByTitle extends Command
{
    protected $signature = 'images:rename-by-title {--dry-run : Solo muestra cambios sin ejecutarlos}';

    protected $description = 'Renombra archivos de imagen en disco según el texto_alt_SEO de cada registro en BD';

    private ProductoImageService $imageService;

    public function __construct(ProductoImageService $imageService)
    {
        parent::__construct();
        $this->imageService = $imageService;
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? 'Modo DRY-RUN: no se renombrará nada.' : 'Renombrando imágenes...');

        $renamed = 0;
        $skipped = 0;

        // 1. producto_imagenes (galería, popup, etc.)
        $imagenes = ProductoImagen::all();

        foreach ($imagenes as $imagen) {
            $newUrl = $this->renameOne($imagen->url_imagen, $imagen->texto_alt_SEO ?? '', $dryRun);

            if ($newUrl) {
                if (!$dryRun) {
                    $imagen->update(['url_imagen' => $newUrl]);
                }
                $renamed++;
            } else {
                $skipped++;
            }
        }

        // 2. producto_whatsapp_pasos (usa nombre del producto como base)
        $whatsappPasos = ProductoWhatsappPaso::whereNotNull('imagen_url')
            ->where('imagen_url', '!=', '')
            ->with('producto')
            ->get();

        foreach ($whatsappPasos as $paso) {
            $textoAlt = $paso->producto->nombre ?? 'whatsapp_' . $paso->paso;
            $newUrl = $this->renameOne($paso->imagen_url, $textoAlt, $dryRun);

            if ($newUrl) {
                if (!$dryRun) {
                    $paso->update(['imagen_url' => $newUrl]);
                }
                $renamed++;
            } else {
                $skipped++;
            }
        }

        // 3. producto_email_pasos (usa nombre del producto como base)
        $emailPasos = ProductoEmailPaso::whereNotNull('imagen_url')
            ->where('imagen_url', '!=', '')
            ->with('producto')
            ->get();

        foreach ($emailPasos as $paso) {
            $textoAlt = $paso->producto->nombre ?? 'email_' . $paso->paso;
            $newUrl = $this->renameOne($paso->imagen_url, $textoAlt, $dryRun);

            if ($newUrl) {
                if (!$dryRun) {
                    $paso->update(['imagen_url' => $newUrl]);
                }
                $renamed++;
            } else {
                $skipped++;
            }
        }

        $this->newLine();
        $this->info("Resumen:");
        $this->table(
            ['Resultado', 'Cantidad'],
            [
                ['Renombradas', $renamed],
                ['Saltadas (sin cambios)', $skipped],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Renombra un solo archivo de imagen en disco.
     *
     * @return string|null Nueva URL si se renombró, null si se saltó o hubo error
     */
    private function renameOne(string $currentUrl, string $textoAlt, bool $dryRun): ?string
    {
        $currentRelativePath = str_replace('/storage/', '', $currentUrl);
        $extension = strtolower(pathinfo($currentRelativePath, PATHINFO_EXTENSION));

        // Si no hay texto_alt_SEO, usar el nombre original del archivo como base
        if (empty($textoAlt)) {
            $textoAlt = pathinfo($currentRelativePath, PATHINFO_FILENAME);
        }

        $base = preg_replace('/[^A-Za-z0-9._\-]+/', '_', trim($textoAlt));
        if ($base === '' || $base === '.') {
            return null;
        }

        $newNombre = $base . '_' . Str::random(6) . '.' . $extension;
        $newRelativePath = 'imagenes/' . $newNombre;

        if (!Storage::disk('public')->exists($currentRelativePath)) {
            $this->warn("  [NO EXISTE] {$currentRelativePath}");
            return null;
        }

        if ($currentRelativePath === $newRelativePath) {
            return null;
        }

        $newUrl = '/storage/' . $newRelativePath;

        if ($dryRun) {
            $this->line("  [DRY-RUN] {$currentRelativePath} -> {$newRelativePath}");
            return $newUrl;
        }

        Storage::disk('public')->move($currentRelativePath, $newRelativePath);
        $this->line("  {$currentRelativePath} -> {$newRelativePath}");

        return $newUrl;
    }
}
