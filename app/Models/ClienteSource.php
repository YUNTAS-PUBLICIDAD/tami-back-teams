<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClienteSource extends Model
{
    use HasFactory;
    protected $table = "cliente_sources";

    protected $fillable = [
        'name',
    ];

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'source_id');
    }
}
