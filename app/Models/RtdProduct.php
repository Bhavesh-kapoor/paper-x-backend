<?php

namespace App\Models;

use App\Enums\RTDLeadTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class RtdProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rtd_products';

    protected $fillable = [
        'converter_id',
        'category',
        'product_name',
        'image_path',
        'size',
        'size_unit',
        'material_id',
        'material',
        'material_custom',
        'thickness',
        'thickness_unit',
        'finish_ids',
        'finish',
        'branding_methods',
        'branding_method',
        'lead_time',
        'moq',
        'max_capacity',
        'base_price',
        'gst_rate',
        'buy_now_enabled',
        'decline_count',
        'visibility_score',
        'status',
        'delivery_geography',
        'location_id',
        'location_source',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'lead_time'       => RTDLeadTime::class,
        'material_id'     => 'integer',
        'finish_ids'      => 'array',
        'branding_methods'=> 'array',
        'moq'             => 'integer',
        'max_capacity'    => 'integer',
        'base_price'      => 'decimal:2',
        'buy_now_enabled' => 'boolean',
        'decline_count'   => 'integer',
        'visibility_score'=> 'integer',
        'location_id'     => 'integer',
        'latitude'        => 'float',
        'longitude'       => 'float',
    ];

    // ── Relationships ──

    public function converter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converter_id');
    }

    public function priceSlabs(): HasMany
    {
        return $this->hasMany(RtdPriceSlab::class, 'product_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(RtdOrder::class, 'product_id');
    }

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', 'active')
                     ->where('visibility_score', '>', 0);
    }
}
