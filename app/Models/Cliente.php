<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = "clientes";
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'celular',
        'producto_id',
        'source_id',
    ];

    public $timestamps = true;

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(ClienteSource::class, 'source_id');
    }
}