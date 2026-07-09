<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Blog;

class BlogImagenes extends Model
{
    protected $table = 'blogs_imagenes';
    protected $primaryKey = 'id';
    protected $fillable = [
        'ruta_imagen',
        'img_alt',
        'img_nombre',
        'img_tittle',
        //'text_alt',
        'blog_id'
    ];

    public $timestamps = true;
    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class, 'blog_id');
    }   
}
