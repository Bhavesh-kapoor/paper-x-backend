<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'sender_user_id',
        'sender_role',
        'body',
        'attachment',
        'status',
    ];

    protected $casts = [
        'thread_id' => 'integer',
        'sender_user_id' => 'integer',
        'sender_role' => 'string',
        'body' => 'string',
        'attachment' => 'string',
        'status' => 'string',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class, 'thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
