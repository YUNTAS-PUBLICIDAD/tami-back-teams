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

        'popup_button_color',
        'popup_text_color'
    ];

    protected $casts = [
        'popup3_sin_fondo' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
