<?php

namespace App\Models;

use App\Enums\AvailabilityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAvailabilityHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'availability_type',
    ];

    protected $casts = [
        'availability_type' => AvailabilityType::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
