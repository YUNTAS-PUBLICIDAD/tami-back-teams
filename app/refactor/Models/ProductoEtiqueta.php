<?php

namespace App\Refactor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoEtiqueta extends Model
{
    use HasFactory;

    protected $fillable = ['producto_id', 'meta_titulo', 'meta_descripcion', 'keywords'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}

