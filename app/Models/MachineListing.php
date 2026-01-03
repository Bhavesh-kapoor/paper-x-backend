<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_dealer_id',
        'machine_id',
        'machine_brand_id',
        'machine_type',
        'condition',
        'intent',
        'urgency',
        'description',
        'attachments',
        'price',
        'currency',
        'location',
        'latitude',
        'longitude',
        'status',
        'posting_fee_paid',
        'posting_fee_amount',
    ];

    protected $casts = [
        'attachments' => 'array',
        'price' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'posting_fee_paid' => 'boolean',
        'posting_fee_amount' => 'decimal:2',
    ];

    public function machineDealer(): BelongsTo
    {
        return $this->belongsTo(MachineDealer::class, 'machine_dealer_id');
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function machineBrand(): BelongsTo
    {
        return $this->belongsTo(MachineBrand::class, 'machine_brand_id');
    }
}
