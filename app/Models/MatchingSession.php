<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'inquiry_id',
        'status',
        'discovery_start',
        'discovery_end',
        'active_session_start',
        'locked_at',
        'expires_at',
        'winning_dealer_id',
        'republish_count',
        'republished_at',
        'republish_cooldown_until',
        'is_night_mode',
        'full_matching_starts_at',
    ];

    protected $casts = [
        'status' => SessionStatus::class,
        'discovery_start' => 'datetime',
        'discovery_end' => 'datetime',
        'active_session_start' => 'datetime',
        'locked_at' => 'datetime',
        'expires_at' => 'datetime',
        'republish_count' => 'integer',
        'republished_at' => 'datetime',
        'republish_cooldown_until' => 'datetime',
        'is_night_mode' => 'boolean',
        'full_matching_starts_at' => 'datetime',
    ];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function winningDealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'winning_dealer_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'session_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'session_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class, 'session_id');
    }
}
