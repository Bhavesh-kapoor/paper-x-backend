<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RtdOrderPaymentOrder extends Model
{
    public const STATUS_CREATED = 'created';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    protected $table = 'rtd_order_payment_orders';

    protected $fillable = [
        'rtd_order_id',
        'user_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'receipt',
        'amount_paise',
        'currency',
        'status',
        'metadata',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_paise' => 'integer',
            'metadata' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function rtdOrder(): BelongsTo
    {
        return $this->belongsTo(RtdOrder::class, 'rtd_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
