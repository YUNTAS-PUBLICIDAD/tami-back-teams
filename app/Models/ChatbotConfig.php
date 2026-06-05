<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotConfig extends Model
{
    protected $fillable = ['url_icono', 'colores_header', 'salute', 'is_left'];
}

