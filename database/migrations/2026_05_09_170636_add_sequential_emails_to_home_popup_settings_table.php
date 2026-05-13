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
        Schema::table('home_popup_settings', function (Blueprint $table) {
            // Email 1 delay
            $table->integer('email_send_delay_minutes')->default(0)->after('email_btn_text_color');

            // Email 2
            $table->string('email_subject_2')->nullable()->after('email_send_delay_minutes');
            $table->text('email_message_2')->nullable()->after('email_subject_2');
            $table->string('email_image_url_2')->nullable()->after('email_message_2');
            $table->string('email_btn_text_2')->nullable()->after('email_image_url_2');
            $table->string('email_btn_link_2')->nullable()->after('email_btn_text_2');
            $table->string('email_btn_bg_color_2')->nullable()->after('email_btn_link_2');
            $table->string('email_btn_text_color_2')->nullable()->after('email_btn_bg_color_2');
            $table->integer('email_send_delay_minutes_2')->default(30)->after('email_btn_text_color_2');

            // Email 3
            $table->string('email_subject_3')->nullable()->after('email_send_delay_minutes_2');
            $table->text('email_message_3')->nullable()->after('email_subject_3');
            $table->string('email_image_url_3')->nullable()->after('email_message_3');
            $table->string('email_btn_text_3')->nullable()->after('email_image_url_3');
            $table->string('email_btn_link_3')->nullable()->after('email_btn_text_3');
            $table->string('email_btn_bg_color_3')->nullable()->after('email_btn_link_3');
            $table->string('email_btn_text_color_3')->nullable()->after('email_btn_bg_color_3');
            $table->integer('email_send_delay_minutes_3')->default(1440)->after('email_btn_text_color_3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_popup_settings', function (Blueprint $table) {
            $table->dropColumn([
                'email_send_delay_minutes',
                'email_subject_2', 'email_message_2', 'email_image_url_2', 'email_btn_text_2', 'email_btn_link_2', 'email_btn_bg_color_2', 'email_btn_text_color_2', 'email_send_delay_minutes_2',
                'email_subject_3', 'email_message_3', 'email_image_url_3', 'email_btn_text_3', 'email_btn_link_3', 'email_btn_bg_color_3', 'email_btn_text_color_3', 'email_send_delay_minutes_3'
            ]);
        });
    }
};
