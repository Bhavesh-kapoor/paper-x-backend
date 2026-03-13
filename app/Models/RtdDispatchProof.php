<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RtdDispatchProof extends Model
{
    use HasFactory;

    protected $table = 'rtd_dispatch_proofs';

    protected $fillable = [
        'order_id',
        'proof_type',
        'file_path',
        'courier_name',
        'tracking_number',
        'dispatch_date',
    ];

    protected $casts = [
        'dispatch_date' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(RtdOrder::class, 'order_id');
    }
}
