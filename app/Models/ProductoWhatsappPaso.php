<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoWhatsappPaso extends Model
{
    protected $table = 'producto_whatsapp_pasos';

    protected $fillable = [
        'producto_id',
        'paso',
        'mensaje',
        'imagen_url',
        'delay_minutos',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
