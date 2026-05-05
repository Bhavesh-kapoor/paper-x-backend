<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletPaymentOrder extends Model
{
    public const STATUS_CREATED = 'created';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'credit_pack_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'receipt',
        'amount_paise',
        'currency',
        'credits',
        'status',
        'wallet_transaction_id',
        'metadata',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_paise' => 'integer',
            'credits' => 'integer',
            'metadata' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creditPack(): BelongsTo
    {
        return $this->belongsTo(CreditPack::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CREATED);
    }
}
