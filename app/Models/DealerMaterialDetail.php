<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerMaterialDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'dealer_id',
        'material_id',
        'brand_id',
        'agent_type',
        'finish_ids',
        'thickness_ranges',
    ];

    protected $casts = [
        'finish_ids' => 'array',
        'thickness_ranges' => 'array',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
