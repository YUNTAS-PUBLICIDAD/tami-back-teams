<?php
namespace App\Refactor\Models;

use Illuminate\Database\Eloquent\Model;

class Whatsapp extends Model
{
    protected $table = 'whatsapp';
    protected $fillable = [
        'interesado_id', 'producto_id', 'texto', 'imagen'
    ];

    public function interesado()
    {
        return $this->belongsTo(Interesado::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
