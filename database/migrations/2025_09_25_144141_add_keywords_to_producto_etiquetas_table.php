<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('producto_etiquetas', function (Blueprint $table) {
            $table->text('keywords')->nullable()->after('meta_descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('producto_etiquetas', function (Blueprint $table) {
            $table->dropColumn('keywords');
        });
    }
};
