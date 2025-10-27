<?php

namespace App\Refactor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre', 'descripcion', 'precio', 'stock',
        'link', 'video_url', 'alto', 'largo', 'ancho'
    ];

    public function etiquetas()
    {
        return $this->hasOne(ProductoEtiqueta::class);
    }

    public function imagenes()
    {
        return $this->hasMany(ProductoImagen::class);
    }

    public function especificaciones()
    {
        return $this->hasMany(Especificacion::class);
    }

    public function relacionados()
    {
        return $this->hasMany(ProductoRelacionado::class, 'producto_id');
    }

    public function relacionadosConmigo()
    {
        return $this->hasMany(ProductoRelacionado::class, 'producto_relacionado_id');
    }

    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }

    public function whatsapp()
    {
        return $this->hasMany(Whatsapp::class);
    }
}
