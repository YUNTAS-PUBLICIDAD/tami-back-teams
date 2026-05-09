<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'detalle_titulo_tamano')) {
                $table->unsignedInteger('detalle_titulo_tamano')->nullable()->after('titulo');
            }

            if (!Schema::hasColumn('productos', 'detalle_titulo_color')) {
                $table->string('detalle_titulo_color', 20)->nullable()->after('detalle_titulo_tamano');
            }

            if (!Schema::hasColumn('productos', 'detalle_titulo_estilo')) {
                $table->string('detalle_titulo_estilo', 50)->nullable()->after('detalle_titulo_color');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $toDrop = [];

            if (Schema::hasColumn('productos', 'detalle_titulo_estilo')) {
                $toDrop[] = 'detalle_titulo_estilo';
            }

            if (Schema::hasColumn('productos', 'detalle_titulo_color')) {
                $toDrop[] = 'detalle_titulo_color';
            }

            if (Schema::hasColumn('productos', 'detalle_titulo_tamano')) {
                $toDrop[] = 'detalle_titulo_tamano';
            }

            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }
};
