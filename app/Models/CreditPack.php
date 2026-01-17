<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditPack extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'credits',
        'price',
        'gst_percentage',
        'description',
        'validity',
        'is_best_value',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'credits' => 'integer',
        'price' => 'decimal:2',
        'gst_percentage' => 'decimal:2',
        'is_best_value' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getTotalPriceAttribute()
    {
        $gstAmount = ($this->price * $this->gst_percentage) / 100;
        return $this->price + $gstAmount;
    }

    public function getGstAmountAttribute()
    {
        return ($this->price * $this->gst_percentage) / 100;
    }
}
