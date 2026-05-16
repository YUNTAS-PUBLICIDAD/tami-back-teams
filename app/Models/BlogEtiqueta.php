<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogEtiqueta extends Model
{
    use HasFactory;

    protected $table = 'blog_etiquetas';

    protected $fillable = [
        'blog_id',
        'meta_titulo',
        'meta_descripcion',
        'popup_button_text',
        'popup_button_color',
        'popup_text_color',
    ];

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}
