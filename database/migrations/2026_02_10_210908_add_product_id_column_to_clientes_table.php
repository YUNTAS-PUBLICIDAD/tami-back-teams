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
        Schema::table('clientes', function (Blueprint $table) {
            $table->unsignedBigInteger('producto_id')->nullable()->after('celular');
            $table->unsignedBigInteger('source_id')->nullable()->after('producto_id');

            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('set null');
            $table->foreign('source_id')->references('id')->on('cliente_sources')->onDelete('set null');
            
            $table->index('producto_id');
            $table->index('source_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropForeign(['source_id']);
            $table->dropIndex(['producto_id']);
            $table->dropIndex(['source_id']);
            $table->dropColumn('producto_id');
            $table->dropColumn('source_id');
        });
    }
};
