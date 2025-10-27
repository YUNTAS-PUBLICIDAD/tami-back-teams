<?php

namespace App\Refactor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoRelacionado extends Model
{
    use HasFactory;

    protected $fillable = ['producto_id', 'producto_relacionado_id'];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function relacionado()
    {
        return $this->belongsTo(Producto::class, 'producto_relacionado_id');
    }
}
