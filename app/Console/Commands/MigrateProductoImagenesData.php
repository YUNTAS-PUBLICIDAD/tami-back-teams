<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Comando de la FASE "MIGRATE" del patrón Expand -> Migrate -> Contract.
 *
 * Copia los datos de mensajería (WhatsApp y Email) que hoy viven mezclados
 * en `producto_imagenes` hacia las nuevas tablas `producto_whatsapp_pasos`
 * y `producto_email_pasos`.
 *
 * NO borra ni modifica `producto_imagenes` (eso es la FASE "CONTRACT",
 * en una migración separada, a ejecutar solo tras validar en staging).
 *
 * Uso:
 *   php artisan migrate:producto-imagenes-data            -> ejecuta la copia
 *   php artisan migrate:producto-imagenes-data --dry-run   -> solo muestra qué haría, sin escribir
 */
class MigrateProductoImagenesData extends Command
{
    protected $signature = 'migrate:producto-imagenes-data {--dry-run : Solo simula, no escribe en la base de datos}';

    protected $description = 'Migra datos de mensajería WhatsApp/Email desde producto_imagenes a sus nuevas tablas dedicadas';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? 'Modo DRY-RUN: no se escribirá nada.' : 'Modo REAL: se escribirán datos.');

        DB::beginTransaction();

        try {
            $whatsappCount = $this->migrarWhatsapp($dryRun);
            $emailCount = $this->migrarEmail($dryRun);

            if ($dryRun) {
                DB::rollBack();
                $this->info("[DRY-RUN] Se migrarían {$whatsappCount} pasos de WhatsApp y {$emailCount} pasos de Email.");
            } else {
                DB::commit();
                $this->info("Migrados {$whatsappCount} pasos de WhatsApp y {$emailCount} pasos de Email.");
                $this->verificarConteos();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error durante la migración: '.$e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function migrarWhatsapp(bool $dryRun): int
    {
        $filas = DB::table('producto_imagenes')
            ->where('tipo', 'whatsapp')
            ->get();

        $count = 0;

        foreach ($filas as $fila) {
            // Paso 1
            if (!empty($fila->whatsapp_mensaje)) {
                $count += $this->insertarWhatsappPaso($fila, 1, $fila->whatsapp_mensaje, $fila->url_imagen, $fila->whatsapp_time_1, $dryRun);
            }
            // Paso 2
            if (!empty($fila->whatsapp_mensaje_2)) {
                $count += $this->insertarWhatsappPaso($fila, 2, $fila->whatsapp_mensaje_2, $fila->whatsapp_image_url_2, $fila->whatsapp_time_2, $dryRun);
            }
            // Paso 3
            if (!empty($fila->whatsapp_mensaje_3)) {
                $count += $this->insertarWhatsappPaso($fila, 3, $fila->whatsapp_mensaje_3, $fila->whatsapp_image_url_3, $fila->whatsapp_time_3, $dryRun);
            }
        }

        return $count;
    }

    private function insertarWhatsappPaso($fila, int $paso, string $mensaje, ?string $imagenUrl, int $delay, bool $dryRun): int
    {
        if ($dryRun) {
            $this->line("[WHATSAPP] producto_id={$fila->producto_id} paso={$paso} mensaje=".substr($mensaje, 0, 30)."...");
            return 1;
        }

        DB::table('producto_whatsapp_pasos')->updateOrInsert(
            ['producto_id' => $fila->producto_id, 'paso' => $paso],
            [
                'mensaje' => $mensaje,
                'imagen_url' => $imagenUrl,
                'delay_minutos' => $delay ?? 0,
                'created_at' => $fila->created_at,
                'updated_at' => now(),
            ]
        );

        return 1;
    }

    private function migrarEmail(bool $dryRun): int
    {
        $filas = DB::table('producto_imagenes')
            ->where('tipo', 'like', 'email%')
            ->get();

        $count = 0;

        foreach ($filas as $fila) {
            // El paso viene codificado en el propio valor de "tipo": email1, email2, email3
            $paso = (int) preg_replace('/\D/', '', $fila->tipo);
            $paso = $paso > 0 ? $paso : ($fila->slot_index ?? 1);

            if ($dryRun) {
                $this->line("[EMAIL] producto_id={$fila->producto_id} paso={$paso} asunto={$fila->asunto}");
                $count++;
                continue;
            }

            DB::table('producto_email_pasos')->updateOrInsert(
                ['producto_id' => $fila->producto_id, 'paso' => $paso],
                [
                    'asunto' => $fila->asunto,
                    'mensaje' => $fila->email_mensaje,
                    'imagen_url' => $fila->url_imagen ?: null,
                    'btn_text' => $fila->email_btn_text,
                    'btn_link' => $fila->email_btn_link,
                    'btn_bg_color' => $fila->email_btn_bg_color,
                    'btn_text_color' => $fila->email_btn_text_color,
                    'delay_minutos' => $fila->delay_minutes ?? 0,
                    'created_at' => $fila->created_at,
                    'updated_at' => now(),
                ]
            );
            $count++;
        }

        return $count;
    }

    private function verificarConteos(): void
    {
        $origenWhatsapp = DB::table('producto_imagenes')->where('tipo', 'whatsapp')->count();
        $destinoWhatsapp = DB::table('producto_whatsapp_pasos')->count();

        $origenEmail = DB::table('producto_imagenes')->where('tipo', 'like', 'email%')->count();
        $destinoEmail = DB::table('producto_email_pasos')->count();

        $this->table(
            ['Entidad', 'Filas origen (producto_imagenes)', 'Filas destino (tabla nueva)'],
            [
                ['WhatsApp', $origenWhatsapp, $destinoWhatsapp],
                ['Email', $origenEmail, $destinoEmail],
            ]
        );

        $this->warn('Revisa que los conteos sean coherentes (recuerda: 1 fila origen puede generar hasta 3 pasos de WhatsApp).');
    }
}
