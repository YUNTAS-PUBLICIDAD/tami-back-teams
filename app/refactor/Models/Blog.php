<?php
namespace App\Refactor\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $table = 'blogs';
    protected $fillable = [
        'producto_id', 'user_id', 'titulo', 'link', 'subtitulo1', 'subtitulo2', 'video_url', 'video_titulo', 'miniatura'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function etiquetas()
    {
        return $this->hasMany(BlogEtiqueta::class);
    }

    public function imagenes()
    {
        return $this->hasMany(BlogImagen::class);
    }

    public function parrafos()
    {
        return $this->hasMany(BlogParrafo::class);
    }
}
