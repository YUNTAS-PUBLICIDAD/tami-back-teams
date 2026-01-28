<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
    ];

    /**
     * Get the claims for the document type.
     */
    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }
}
