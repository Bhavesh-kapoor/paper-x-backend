<?php

namespace App\Models;

use App\Enums\AcceptanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerAcceptance extends Model
{
    use HasFactory;

    protected $fillable = [
        'dealer_id',
        'inquiry_id',
        'status',
        'decline_reason',
    ];

    protected $casts = [
        'status' => AcceptanceStatus::class,
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }
}
