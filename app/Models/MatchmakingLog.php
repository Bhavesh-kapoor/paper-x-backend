<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchmakingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'inquiry_id',
        'dealer_id',
        'session_id',
        'material_match',
        'finish_match',
        'thickness_match',
        'location_match',
        'thickness_tolerance_percent',
        'thickness_tolerance_absolute',
        'priority_score',
        'score_breakdown',
        'visible_to_dealer_at',
        'hidden_from_dealer_at',
        'is_visible',
        'responded_at',
        'response_id',
        'is_selected',
        'selected_at',
    ];

    protected $casts = [
        'material_match' => 'boolean',
        'finish_match' => 'boolean',
        'thickness_match' => 'boolean',
        'location_match' => 'boolean',
        'thickness_tolerance_percent' => 'decimal:2',
        'thickness_tolerance_absolute' => 'decimal:3',
        'priority_score' => 'integer',
        'score_breakdown' => 'array',
        'is_visible' => 'boolean',
        'is_selected' => 'boolean',
        'visible_to_dealer_at' => 'datetime',
        'hidden_from_dealer_at' => 'datetime',
        'responded_at' => 'datetime',
        'selected_at' => 'datetime',
    ];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(MatchingSession::class);
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(Response::class);
    }

    // Scopes
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeSelected($query)
    {
        return $query->where('is_selected', true);
    }

    public function scopeForInquiry($query, $inquiryId)
    {
        return $query->where('inquiry_id', $inquiryId);
    }

    public function scopeForDealer($query, $dealerId)
    {
        return $query->where('dealer_id', $dealerId);
    }
}
