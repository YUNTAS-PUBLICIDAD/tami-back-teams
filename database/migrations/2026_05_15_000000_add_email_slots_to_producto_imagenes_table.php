<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('producto_imagenes', function (Blueprint $table) {
            if (!Schema::hasColumn('producto_imagenes', 'slot_index')) {
                $table->unsignedTinyInteger('slot_index')->nullable()->after('tipo');
            }

            if (!Schema::hasColumn('producto_imagenes', 'delay_minutes')) {
                $table->integer('delay_minutes')->default(0)->after('email_btn_text_color');
            }
        });

        DB::table('producto_imagenes')
            ->where('tipo', 'email')
            ->update([
                'tipo' => 'email1',
                'slot_index' => 1,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producto_imagenes', function (Blueprint $table) {
            if (Schema::hasColumn('producto_imagenes', 'slot_index')) {
                $table->dropColumn('slot_index');
            }

            if (Schema::hasColumn('producto_imagenes', 'delay_minutes')) {
                $table->dropColumn('delay_minutes');
            }
        });
    }
};
