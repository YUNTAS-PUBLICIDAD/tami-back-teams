<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoImagen extends Model
{
    protected $table = "producto_imagenes";
    
    protected $fillable = [
        'url_imagen',
        'original_name',
        'texto_alt_SEO',
        'titulo',
        'tipo',
        'producto_id',
    ];

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