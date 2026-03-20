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
        Schema::create('home_popup_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);

            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('popup_image_url')->nullable();

            $table->string('button_text')->default('!REGISTRARME!');
            $table->string('button_bg_color', 7)->default('#00AFA0');
            $table->string('button_text_color', 7)->default('#FFFFFF');

            $table->boolean('whatsapp_enabled')->default(false);
            $table->text('whatsapp_message')->nullable();
            $table->string('whatsapp_image_url')->nullable();

            $table->boolean('email_enabled')->default(false);
            $table->string('email_subject')->nullable();
            $table->text('email_message')->nullable();
            $table->string('email_image_url')->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_popup_settings');
    }
};
