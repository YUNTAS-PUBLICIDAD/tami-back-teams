<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            // Eliminar la columna 'name' si existe
            $table->dropColumn('name');
            
            // Agregar la nueva columna producto_id
            $table->foreignId('producto_id')->unique()->after('id')->constrained('productos')->onDelete('cascade');
            
            // Cambiar el tipo de columna 'content' de JSON a TEXT
            $table->text('content')->change();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            // Revertir los cambios
            $table->dropForeign(['producto_id']);
            $table->dropColumn('producto_id');
            $table->string('name')->unique();
            $table->json('content')->change();
        });
    }
};