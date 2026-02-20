<?php

namespace App\Models;

use App\Enums\RTDPayoutStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RtdPayout extends Model
{
    use HasFactory;

    protected $table = 'rtd_payouts';

    protected $fillable = [
        'order_id',
        'converter_id',
        'amount',
        'payout_status',
        'released_at',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'payout_status' => RTDPayoutStatus::class,
        'released_at'   => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(RtdOrder::class, 'order_id');
    }

    public function converter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converter_id');
    }
}
