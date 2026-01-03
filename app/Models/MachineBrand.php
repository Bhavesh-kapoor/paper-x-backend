<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MachineBrand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'machine_category',
        'sort_order',
    ];

    public function machineListings(): HasMany
    {
        return $this->hasMany(MachineListing::class, 'machine_brand_id');
    }
}
