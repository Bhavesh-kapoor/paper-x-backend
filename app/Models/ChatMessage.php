<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'sender_id',
        'sender_type',
        'message',
        'attachment_path',
        'status',
    ];

    protected $casts = [
        'status' => 'string', // SENT, DELIVERED, READ
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(MatchingSession::class, 'session_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
