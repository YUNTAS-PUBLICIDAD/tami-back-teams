<?php

namespace App\Refactor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogEtiqueta extends Model
{
    use HasFactory;

    protected $fillable = ['blog_id', 'meta_titulo', 'meta_descripcion'];

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}
