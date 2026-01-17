<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'notifiable_type',
        'notifiable_id',
        'read',
        'read_at',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo('notifiable', 'notifiable_type', 'notifiable_id');
    }
    
    /**
     * Override to prevent accessing notifiable when type is User
     */
    public function __get($key)
    {
        if ($key === 'notifiable') {
            $notifiableType = $this->getAttribute('notifiable_type');
            if ($notifiableType && (
                $notifiableType === 'App\\Models\\User' || 
                $notifiableType === 'user' || 
                $notifiableType === 'User' ||
                str_contains($notifiableType, 'User')
            )) {
                return null;
            }
        }
        
        try {
            return parent::__get($key);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'morph map') && $key === 'notifiable') {
                return null;
            }
            throw $e;
        }
    }
}
