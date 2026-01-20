<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SessionParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'participant_type',
        'participant_id',
        'role',
        'can_see_full_specs',
        'can_see_exact_location',
        'can_see_brand_identity',
        'can_chat',
        'status',
        'joined_at',
        'left_at',
        'is_selected',
        'selected_at',
    ];

    protected $casts = [
        'can_see_full_specs' => 'boolean',
        'can_see_exact_location' => 'boolean',
        'can_see_brand_identity' => 'boolean',
        'can_chat' => 'boolean',
        'is_selected' => 'boolean',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'selected_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(MatchingSession::class);
    }

    public function participant(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSelected($query)
    {
        return $query->where('is_selected', true);
    }

    public function scopePosters($query)
    {
        return $query->where('role', 'poster');
    }

    public function scopeResponders($query)
    {
        return $query->where('role', 'responder');
    }

    public function scopeCanChat($query)
    {
        return $query->where('can_chat', true);
    }
}
