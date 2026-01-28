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
            $table->enum('popup_estilo', ['estilo1', 'estilo2', 'estilo3'])
                ->default('estilo1')
                ->after('keywords');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producto_etiquetas', function (Blueprint $table) {
            $table->dropColumn('popup_estilo');
        });
    }
};
