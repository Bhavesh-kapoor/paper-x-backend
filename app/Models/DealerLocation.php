<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'dealer_id',
        'type',
        'address',
        'latitude',
        'longitude',
        'city',
        'state',
        'pincode',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }
}
