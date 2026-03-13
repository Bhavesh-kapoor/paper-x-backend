<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RtdListingPack extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'product_limit',
        'validity_days',
        'price',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'product_limit' => 'integer',
        'validity_days' => 'integer',
        'price'         => 'decimal:2',
        'is_active'     => 'boolean',
        'sort_order'    => 'integer',
    ];

    public function entitlements(): HasMany
    {
        return $this->hasMany(RtdConverterEntitlement::class, 'pack_slug', 'slug');
    }
}
