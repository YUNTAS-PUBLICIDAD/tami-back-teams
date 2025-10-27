<?php

namespace App\Refactor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogParrafo extends Model
{
    use HasFactory;

    protected $fillable = ['parrafo', 'blog_id'];

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}
