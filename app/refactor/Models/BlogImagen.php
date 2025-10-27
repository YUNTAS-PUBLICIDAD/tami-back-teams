<?php

namespace App\Refactor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogImagen extends Model
{
    use HasFactory;

    protected $fillable = ['ruta_imagen', 'text_alt', 'blog_id'];

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}
