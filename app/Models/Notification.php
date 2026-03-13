<?php

namespace App\Models;

use App\Enums\NavigationType;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'meta',
        'navigation_type',
        'navigation_id',
        'read_at',
        'dedupe_key',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'navigation_type' => NavigationType::class,
        'meta' => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
