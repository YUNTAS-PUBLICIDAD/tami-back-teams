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
        'email_enabled',
        'email_subject',
        'email_message',
        'email_image_url',
        'email_btn_text',
        'email_btn_link',
        'email_btn_bg_color',
        'email_btn_text_color',
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
        'email_enabled' => 'boolean',
    ];
}
