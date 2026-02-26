<?php

namespace App\Domain\MatchEngine\Models;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InquiryResponse extends Model
{
    protected $table = 'inquiry_responses';

    protected $fillable = [
        'inquiry_id',
        'responder_id',
        'responder_role',
        'approx_price',
        'description',
        'responded_at',
    ];

    protected $casts = [
        'approx_price' => 'decimal:2',
        'responded_at' => 'datetime',
    ];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_id');
    }
}
