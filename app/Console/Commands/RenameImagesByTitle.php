<?php

namespace App\Console\Commands;

use App\Models\ProductoImagen;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RenameImagesByTitle extends Command
{
    protected $signature = 'images:rename-by-title {--dry-run : Solo muestra cambios sin ejecutarlos}';

    protected $description = 'Renombra archivos de imagen en disco según el texto_alt_SEO de cada registro en producto_imagenes';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? 'Modo DRY-RUN: no se renombrará nada.' : 'Renombrando imágenes de producto_imagenes...');

        $renamed = 0;
        $skipped = 0;

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
