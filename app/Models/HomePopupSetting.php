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
        'button_text',
        'button_bg_color',
        'button_text_color',
        'whatsapp_enabled',
        'whatsapp_message',
        'whatsapp_image_url',
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
        'email_enabled' => 'boolean',
    ];
}
