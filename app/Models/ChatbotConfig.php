<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotConfig extends Model
{
    protected $fillable = ['url_icono', 'color_inicial', 'color_final', 'salute', 'is_left'];
}

