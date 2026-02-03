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
    Schema::table('producto_etiquetas', function (Blueprint $table) {
        $table->boolean('popup3_sin_fondo')
              ->default(false)
              ->after('popup_estilo');
    });
}

public function down(): void
{
    Schema::table('producto_etiquetas', function (Blueprint $table) {
        $table->dropColumn('popup3_sin_fondo');
    });
}

};
