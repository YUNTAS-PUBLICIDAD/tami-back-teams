<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoEtiqueta extends Model
{
    use HasFactory;

    protected $table = 'producto_etiquetas';

    protected $fillable = [
        'producto_id',
        'meta_titulo',
        'meta_descripcion',
        'keywords',
        'popup_estilo'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
