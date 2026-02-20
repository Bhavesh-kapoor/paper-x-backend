<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RtdPriceSlab extends Model
{
    use HasFactory;

    protected $table = 'rtd_price_slabs';

    protected $fillable = [
        'product_id',
        'min_qty',
        'max_qty',
        'price_per_unit',
    ];

    protected $casts = [
        'min_qty'        => 'integer',
        'max_qty'        => 'integer',
        'price_per_unit' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(RtdProduct::class, 'product_id');
    }
}
