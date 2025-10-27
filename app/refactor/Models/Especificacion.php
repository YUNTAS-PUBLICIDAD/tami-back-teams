<?php

namespace App\Refactor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Especificacion extends Model
{
    use HasFactory;

    protected $fillable = ['valor', 'texto', 'producto_id'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
