<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BrandType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'sort_order',
    ];

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'brand_brand_types');
    }
}
