<?php

namespace App\Models;

use App\Enums\DealStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'dealer_id',
        'inquiry_id',
        'session_id',
        'quoted_price',
        'currency',
        'delivery_days',
        'notes',
        'deal_status',
    ];

    protected $casts = [
        'quoted_price' => 'decimal:2',
        'deal_status' => DealStatus::class,
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(MatchingSession::class, 'session_id');
    }
}
