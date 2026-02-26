<?php

namespace App\Domain\MatchEngine\Models;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchHistory extends Model
{
    protected $table = 'match_histories';

    protected $fillable = [
        'inquiry_id',
        'matched_user_id',
        'matched_role',
        'match_score',
        'reason_json',
    ];

    protected $casts = [
        'match_score'  => 'integer',
        'reason_json'  => 'array',
    ];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function matchedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_user_id');
    }
}
