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
        Schema::table('producto_imagenes', function (Blueprint $table) {
            $table->string('asunto')->nullable()->after('texto_alt_SEO');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producto_imagenes', function (Blueprint $table) {
            $table->dropColumn('asunto');
        });
    }
};
