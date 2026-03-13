<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RtdConverterEntitlement extends Model
{
    protected $fillable = [
        'user_id',
        'pack_slug',
        'product_limit',
        'used_count',
        'validity_ends_at',
        'purchased_at',
    ];

    protected $casts = [
        'product_limit'   => 'integer',
        'used_count'      => 'integer',
        'validity_ends_at' => 'datetime',
        'purchased_at'    => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pack(): BelongsTo
    {
        return $this->belongsTo(RtdListingPack::class, 'pack_slug', 'slug');
    }

    public function getRemainingSlotsAttribute(): int
    {
        return max(0, $this->product_limit - $this->used_count);
    }

    public function isValid(): bool
    {
        return $this->validity_ends_at->isFuture() && $this->used_count < $this->product_limit;
    }
}
