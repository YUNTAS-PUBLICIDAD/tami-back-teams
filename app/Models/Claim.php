<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'document_type_id',
        'document_number',
        'email',
        'phone',
        'purchase_date',
        'producto_id',
        'claim_type_id',
        'detail',
        'claimed_amount',
        'claim_status_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'claimed_amount' => 'decimal:2',
    ];

    /**
     * Get the document type for the claim.
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * Get the product for the claim.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the claim type for the claim.
     */
    public function claimType(): BelongsTo
    {
        return $this->belongsTo(ClaimType::class);
    }

    /**
     * Get the claim status for the claim.
     */
    public function claimStatus(): BelongsTo
    {
        return $this->belongsTo(ClaimStatus::class);
    }

    /**
     * Get the responses for the claim.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(ClaimResponse::class);
    }
}