<?php

namespace App\Models;

use App\Enums\ResponseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Response extends Model
{
    use HasFactory;

    protected $fillable = [
        'inquiry_id',
        'responder_id',
        'responder_type',
        'quantity_offered',
        'quantity_unit',
        'quoted_price',
        'price_unit',
        'price_status',
        'additional_details',
        'status',
        'session_id',
    ];

    protected $casts = [
        'quantity_offered' => 'decimal:2',
        'quoted_price' => 'decimal:2',
        'status' => ResponseStatus::class,
    ];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(MatchingSession::class, 'session_id');
    }
}
