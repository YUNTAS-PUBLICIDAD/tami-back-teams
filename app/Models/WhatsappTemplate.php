<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Producto;

class WhatsappTemplate extends Model
{
    protected $fillable = ['producto_id', 'content'];

    /**
     * Relación inversa con Producto
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}