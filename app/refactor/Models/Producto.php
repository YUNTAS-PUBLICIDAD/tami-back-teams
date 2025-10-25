<?php
namespace App\Refactor\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';
    protected $fillable = [
        'nombre', 'descripcion', 'precio', 'stock', 'link', 'video_url'
    ];

    public function etiquetas()
    {
        return $this->hasMany(ProductoEtiqueta::class);
    }

    public function imagenes()
    {
        return $this->hasMany(ProductoImagen::class);
    }

    public function especificaciones()
    {
        return $this->hasMany(Especificacion::class);
    }

    public function dimensiones()
    {
        return $this->hasOne(Dimension::class, 'id_producto');
    }

    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }

    public function whatsappEnvios()
    {
        return $this->hasMany(Whatsapp::class);
    }

    public function relacionados()
    {
        return $this->hasMany(ProductoRelacionado::class, 'producto_id');
    }
}
