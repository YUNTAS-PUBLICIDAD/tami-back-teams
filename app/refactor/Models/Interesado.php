<?php
namespace App\Refactor\Models;

use Illuminate\Database\Eloquent\Model;

class Interesado extends Model
{
    protected $table = 'interesados';
    protected $fillable = [
        'name', 'email', 'celular'
    ];

    public function whatsappEnvios()
    {
        return $this->hasMany(Whatsapp::class);
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'whatsapp', 'interesado_id', 'producto_id');
    }
}
