<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomePopupSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'enabled',
        'popup_start_delay_seconds',
        'product_popup_delay_seconds',
        'title',
        'subtitle',
        'popup_image_url',
        'popup_image_2_url',
        'popup_mobile_image_url',
        'button_text',
        'button_bg_color',
        'button_text_color',
        'whatsapp_enabled',
        'whatsapp_message',
        'whatsapp_message_2',
        'whatsapp_message_3',
        'whatsapp_time_1',
        'whatsapp_time_2',
        'whatsapp_time_3',
        'whatsapp_image_url',
        'whatsapp_image_url_2',
        'whatsapp_image_url_3',
        // Columnas separadas para Inicio
        'whatsapp_image_url_inicio',
        'whatsapp_image_url_2_inicio',
        'whatsapp_image_url_3_inicio',
        'whatsapp_message_inicio',
        'whatsapp_message_2_inicio',
        'whatsapp_message_3_inicio',
        'whatsapp_time_1_inicio',
        'whatsapp_time_2_inicio',
        'whatsapp_time_3_inicio',
        // Columnas separadas para Producto
        'whatsapp_image_url_producto',
        'whatsapp_image_url_2_producto',
        'whatsapp_image_url_3_producto',
        'whatsapp_message_producto',
        'whatsapp_message_2_producto',
        'whatsapp_message_3_producto',
        'whatsapp_time_1_producto',
        'whatsapp_time_2_producto',
        'whatsapp_time_3_producto',
        'email_enabled',
        'email_subject',
        'email_message',
        'email_image_url',
        'email_btn_text',
        'email_btn_link',
        'email_btn_bg_color',
        'email_btn_text_color',
        'email_send_delay_minutes',
        // Email 2
        'email_subject_2',
        'email_message_2',
        'email_image_url_2',
        'email_btn_text_2',
        'email_btn_link_2',
        'email_btn_bg_color_2',
        'email_btn_text_color_2',
        'email_send_delay_minutes_2',
        // Email 3
        'email_subject_3',
        'email_message_3',
        'email_image_url_3',
        'email_btn_text_3',
        'email_btn_link_3',
        'email_btn_bg_color_3',
        'email_btn_text_color_3',
        'email_send_delay_minutes_3',
        'popup_mobile_image2_url',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'popup_start_delay_seconds' => 'integer',
        'product_popup_delay_seconds' => 'integer',
        'whatsapp_enabled' => 'boolean',
        'whatsapp_time_1' => 'integer',
        'whatsapp_time_2' => 'integer',
        'whatsapp_time_3' => 'integer',
        'whatsapp_time_1_inicio' => 'integer',
        'whatsapp_time_2_inicio' => 'integer',
        'whatsapp_time_3_inicio' => 'integer',
        'whatsapp_time_1_producto' => 'integer',
        'whatsapp_time_2_producto' => 'integer',
        'whatsapp_time_3_producto' => 'integer',
        'email_enabled' => 'boolean',
        'email_send_delay_minutes' => 'integer',
        'email_send_delay_minutes_2' => 'integer',
        'email_send_delay_minutes_3' => 'integer',
    ];
}
