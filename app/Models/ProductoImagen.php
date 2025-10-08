<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoImagen extends Model
{
    protected $table = "producto_imagenes";
    protected $fillable = [
        'url_imagen',
        'texto_alt_SEO',
        'tipo',
        'producto_id'
    ];

    /**
     * Scope para filtrar imágenes por tipo
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }
    public $timestamps = true;
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
