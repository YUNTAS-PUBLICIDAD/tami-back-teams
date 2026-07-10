<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoEmailPaso extends Model
{
    protected $table = 'producto_email_pasos';

    protected $fillable = [
        'producto_id',
        'paso',
        'asunto',
        'mensaje',
        'imagen_url',
        'btn_text',
        'btn_link',
        'btn_bg_color',
        'btn_text_color',
        'delay_minutos',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
