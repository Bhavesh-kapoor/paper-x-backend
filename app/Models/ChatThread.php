<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'inquiry_id',
        'poster_user_id',
        'responder_user_id',
        'responder_role',
        'thread_name',
        'thread_type',
        'is_active',
        'is_read_only',
        'archived_at',
        'last_message_at',
        'last_message_id',
    ];

    protected $casts = [
        'inquiry_id' => 'integer',
        'poster_user_id' => 'integer',
        'responder_user_id' => 'integer',
        'responder_role' => 'string',
        'thread_type' => 'string',
        'is_active' => 'boolean',
        'is_read_only' => 'boolean',
        'archived_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(MatchingSession::class);
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class, 'inquiry_id');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'poster_user_id');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_user_id');
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'last_message_id');
    }

    public function lastStructuredMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'thread_id');
    }

    public function legacyMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'session_id', 'session_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeReadOnly($query)
    {
        return $query->where('is_read_only', true);
    }
}
