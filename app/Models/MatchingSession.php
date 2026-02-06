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
        return $this->hasOne(ChatThread::class, 'session_id');
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
            // OR sessions where this converter was matched (MatchmakingLog)
            ->orWhere(function ($matchedQuery) use ($converterId) {
                $matchedQuery->where('is_visible_to_dealers', true)
                    ->whereHas('inquiry.matchmakingLogs', function ($logQuery) use ($converterId) {
                        $logQuery->where('converter_id', $converterId)
                            ->where('is_visible', true);
                    });
            });
        });
    }

    public function scopeVisibleToMachineDealer($query, $machineDealerId)
    {
        return $query->where(function ($q) use ($machineDealerId) {
            // Machine dealer's own posted requirements
            $q->whereHas('inquiry', function ($inqQuery) use ($machineDealerId) {
                $inqQuery->where('poster_type', 'machine_dealer')
                    ->where('poster_id', $machineDealerId);
            })
            // OR sessions where this machine dealer was matched (MatchmakingLog)
            ->orWhere(function ($matchedQuery) use ($machineDealerId) {
                $matchedQuery->where('is_visible_to_dealers', true)
                    ->whereHas('inquiry.matchmakingLogs', function ($logQuery) use ($machineDealerId) {
                        $logQuery->where('machine_dealer_id', $machineDealerId)
                            ->where('is_visible', true);
                    });
            });
        });
    }

    /**
     * Own-sessions-only scopes: each user sees ONLY sessions for inquiries they posted.
     * Sessions are private per user. Matchmaking (common requirements) is separate.
     */
    public function scopeOwnSessionsByDealer($query, $dealerId)
    {
        return $query->whereHas('inquiry', function ($inqQuery) use ($dealerId) {
            $inqQuery->where('poster_type', 'dealer')
                ->where('poster_id', $dealerId);
        });
    }

    public function scopeOwnSessionsByConverter($query, $converterId)
    {
        return $query->whereHas('inquiry', function ($inqQuery) use ($converterId) {
            $inqQuery->where('poster_type', 'converter')
                ->where('poster_id', $converterId);
        });
    }

    public function scopeOwnSessionsByBrand($query, $brandId)
    {
        return $query->whereHas('inquiry', function ($inqQuery) use ($brandId) {
            $inqQuery->where('poster_type', 'brand')
                ->where('poster_id', $brandId);
        });
    }

    public function scopeOwnSessionsByMachineDealer($query, $machineDealerId)
    {
        return $query->whereHas('inquiry', function ($inqQuery) use ($machineDealerId) {
            $inqQuery->where('poster_type', 'machine_dealer')
                ->where('poster_id', $machineDealerId);
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
