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
            $table->string('titulo_popup_1')->nullable()->after('popup_estilo');
            $table->string('titulo_popup_2')->nullable()->after('titulo_popup_1');
            $table->string('titulo_popup_3')->nullable()->after('titulo_popup_2');
        });
    }

    public function down(): void
    {
        Schema::table('producto_etiquetas', function (Blueprint $table) {
            $table->dropColumn([
                'titulo_popup_1',
                'titulo_popup_2',
                'titulo_popup_3'
            ]);
        });
    }
};
