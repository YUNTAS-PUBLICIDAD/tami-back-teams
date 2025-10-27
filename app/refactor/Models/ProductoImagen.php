<?php

namespace App\Refactor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoImagen extends Model
{
    use HasFactory;

    protected $fillable = ['producto_id', 'url_imagen', 'tipo', 'texto_alt'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
