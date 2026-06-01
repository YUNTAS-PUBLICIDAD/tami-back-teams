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
        // Make the texto_alt_SEO column nullable to avoid SQL errors when it's not provided
        Schema::table('producto_imagenes', function (Blueprint $table) {
            if (Schema::hasColumn('producto_imagenes', 'texto_alt_SEO')) {
                $table->string('texto_alt_SEO')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producto_imagenes', function (Blueprint $table) {
            if (Schema::hasColumn('producto_imagenes', 'texto_alt_SEO')) {
                $table->string('texto_alt_SEO')->nullable(false)->change();
            }
        });
    }
};
