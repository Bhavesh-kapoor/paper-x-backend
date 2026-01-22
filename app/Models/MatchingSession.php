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
        // Visibility fields
        'posted_at',
        'matching_started_at',
        'responses_received_at',
        'chat_opened_at',
        'cooldown_until',
        'is_visible_to_dealers',
        'is_visible_to_brand',
        'chat_enabled',
        'full_specs_visible',
        'brand_identity_visible',
        'total_participants',
        'selected_participants_count',
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
        // Visibility casts
        'posted_at' => 'datetime',
        'matching_started_at' => 'datetime',
        'responses_received_at' => 'datetime',
        'chat_opened_at' => 'datetime',
        'cooldown_until' => 'datetime',
        'is_visible_to_dealers' => 'boolean',
        'is_visible_to_brand' => 'boolean',
        'chat_enabled' => 'boolean',
        'full_specs_visible' => 'boolean',
        'brand_identity_visible' => 'boolean',
        'total_participants' => 'integer',
        'selected_participants_count' => 'integer',
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

    public function acceptances(): HasMany
    {
        return $this->hasMany(DealerAcceptance::class, 'inquiry_id', 'inquiry_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(SessionParticipant::class, 'session_id');
    }

    public function chatThread(): HasOne
    {
        return $this->hasOne(ChatThread::class);
    }

    // Visibility Scopes
    public function scopeVisibleToDealer($query, $dealerId)
    {
        return $query->where(function ($q) use ($dealerId) {
            // Dealer's own posted requirements (no participants needed yet)
            $q->whereHas('inquiry', function ($inqQuery) use ($dealerId) {
                $inqQuery->where('poster_type', 'dealer')
                    ->where('poster_id', $dealerId);
            })
            // OR matched dealer inquiries (through participants or matchmaking)
            ->orWhere(function ($matchedQuery) use ($dealerId) {
                $matchedQuery->where('is_visible_to_dealers', true)
                    ->where(function ($participantOrMatchQuery) use ($dealerId) {
                        // Has participant record
                        $participantOrMatchQuery->whereHas('participants', function ($pQuery) use ($dealerId) {
                            $pQuery->where('participant_type', 'dealer')
                                ->where('participant_id', $dealerId)
                                ->where('status', 'active');
                        })
                        // OR has matchmaking log (for newly matched dealers)
                        ->orWhereHas('inquiry.matchmakingLogs', function ($logQuery) use ($dealerId) {
                            $logQuery->where('dealer_id', $dealerId)
                                ->where('is_visible', true);
                        });
                    });
            });
        });
    }

    public function scopeVisibleToBrand($query, $brandId)
    {
        // Brands only see their own posted requirements (brand-posted inquiries)
        // NEVER see dealer-posted requirements
        return $query->where('is_visible_to_brand', true)
            ->whereHas('inquiry', function ($q) use ($brandId) {
                $q->where('poster_id', $brandId)
                    ->where('poster_type', 'brand');
            });
    }

    public function scopeVisibleToConverter($query, $converterId)
    {
        return $query->where(function ($q) use ($converterId) {
            // Converter's own posted requirements
            $q->whereHas('inquiry', function ($inqQuery) use ($converterId) {
                $inqQuery->where('poster_type', 'converter')
                    ->where('poster_id', $converterId);
            })
            // OR dealer-posted requirements visible to converters
            ->orWhereHas('inquiry', function ($inqQuery) {
                $inqQuery->where('poster_type', 'dealer')
                    ->where(function ($visQuery) {
                        $visQuery->where('visibility', 'converters')
                            ->orWhere('visibility', 'all');
                    });
            });
        });
    }

    public function scopeVisibleToMachineDealer($query)
    {
        // Machine dealers see dealer-posted requirements where visibility = 'all'
        return $query->whereHas('inquiry', function ($inqQuery) {
            $inqQuery->where('poster_type', 'dealer')
                ->where('visibility', 'all');
        });
    }

    public function scopeChatEnabled($query)
    {
        return $query->where('chat_enabled', true)
            ->where('status', SessionStatus::LOCKED)
            ->orWhere('status', SessionStatus::CHAT_ACTIVE);
    }

    public function scopeCanRepublish($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('cooldown_until')
                ->orWhere('cooldown_until', '<=', now());
        })
        ->whereIn('status', [
            SessionStatus::DEAL_FAILED,
            SessionStatus::EXPIRED,
            SessionStatus::DEAL_SUCCESS,
        ]);
    }
}
