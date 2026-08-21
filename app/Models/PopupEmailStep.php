<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PopupEmailStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'popup_setting_id',
        'sequence',
        'subject',
        'message',
        'image_url',
        'btn_text',
        'btn_link',
        'btn_bg_color',
        'btn_text_color',
        'delay_minutes',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'delay_minutes' => 'integer',
    ];

    public function popupSetting(): BelongsTo
    {
        return $this->belongsTo(HomePopupSetting::class, 'popup_setting_id');
    }
}