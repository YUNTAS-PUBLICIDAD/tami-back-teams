<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaña extends Model
{
    // Permitimos que estos campos se puedan llenar masivamente
    protected $table = "campañas";
    protected $fillable = [
    'nombre', 
    'producto_id',
    'contenido_personalizado', 
    'imagen_path'];

    // Relación: Una campaña pertenece a un producto
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }


}
