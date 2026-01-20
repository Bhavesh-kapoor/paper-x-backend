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
        'thread_name',
        'thread_type',
        'is_active',
        'is_read_only',
        'archived_at',
        'last_message_at',
        'last_message_id',
    ];

    protected $casts = [
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

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'last_message_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'thread_id');
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
