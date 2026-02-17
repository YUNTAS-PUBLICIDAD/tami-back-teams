<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoEtiqueta extends Model
{
    use HasFactory;

    protected $table = 'producto_etiquetas';
    public $timestamps = false;


    protected $fillable = [
        'producto_id',
        'meta_titulo',
        'meta_descripcion',
        'keywords',
        'popup_estilo',
        'popup3_sin_fondo',
        'titulo_popup_1',
        'titulo_popup_2',
        'titulo_popup_3'
    ];

    protected $casts = [
    'popup3_sin_fondo' => 'boolean', // 👈 CLAVE PARA QUE REACT LO RECIBA COMO true/false
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
