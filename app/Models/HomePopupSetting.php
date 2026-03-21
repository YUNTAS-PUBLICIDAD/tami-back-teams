<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomePopupSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'enabled',
        'popup_start_delay_minutes',
        'product_popup_delay_minutes',
        'title',
        'subtitle',
        'popup_image_url',
        'popup_image2_url',
        'popup_mobile_image_url',
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
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'popup_start_delay_minutes' => 'integer',
        'product_popup_delay_minutes' => 'integer',
        'whatsapp_enabled' => 'boolean',
        'email_enabled' => 'boolean',
    ];
}
