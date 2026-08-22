<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomePopupSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'enabled',
        'popup_start_delay_seconds',
        'popup_image_url',
        'popup_image_2_url',
        'popup_mobile_image_url',
        'popup_mobile_image2_url',
        'button_text',
        'button_bg_color',
        'button_text_color',
        'whatsapp_enabled',
        'email_enabled',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'popup_start_delay_seconds' => 'integer',
        'whatsapp_enabled' => 'boolean',
        'email_enabled' => 'boolean',
    ];

    public function whatsappSteps(): HasMany
    {
        return $this->hasMany(PopupWhatsappStep::class, 'popup_setting_id')->orderBy('sequence');
    }

    public function emailSteps(): HasMany
    {
        return $this->hasMany(PopupEmailStep::class, 'popup_setting_id')->orderBy('sequence');
    }
}