<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BlogImagenes;
use App\Models\BlogParrafos;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Blog extends Model
{
    use HasFactory;

    protected $table = 'blogs';
    protected $primaryKey = 'id';
    protected $fillable = [
        'titulo',
        'producto_id',
        'link',
        'subtitulo1',
        'subtitulo2',
        'video_url',
        'video_titulo',
        'miniatura',
        'created_at',
    ];

    public $timestamps = true;

    public function imagenes(): HasMany
    {
        return $this->hasMany(BlogImagenes::class, 'blog_id'); 
    }

    public function parrafos(): HasMany
    {
        return $this->hasMany(BlogParrafos::class, 'blog_id'); 
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function etiqueta()
    {
        return $this->hasOne(BlogEtiqueta::class);
    }
}
