<?php

namespace App\Models;

use App\Enums\RTDOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class RtdOrder extends Model
{
    use HasFactory;

    protected $table = 'rtd_orders';

    protected $fillable = [
        'product_id',
        'brand_id',
        'converter_id',
        'quantity',
        'logo_path',
        'unit_price',
        'subtotal',
        'commission_percent',
        'commission_amount',
        'gst_percent',
        'gst_amount',
        'total_amount',
        'status',
        'confirmation_deadline',
        'dispatch_deadline',
        'delivery_deadline',
        'payment_status',
        'paid_at',
        'dispatched_at',
        'completed_at',
    ];

    protected $casts = [
        'status'                => RTDOrderStatus::class,
        'quantity'              => 'integer',
        'unit_price'            => 'decimal:2',
        'subtotal'              => 'decimal:2',
        'commission_percent'    => 'decimal:2',
        'commission_amount'     => 'decimal:2',
        'gst_percent'           => 'decimal:2',
        'gst_amount'            => 'decimal:2',
        'total_amount'          => 'decimal:2',
        'confirmation_deadline' => 'datetime',
        'dispatch_deadline'     => 'datetime',
        'delivery_deadline'     => 'datetime',
        'paid_at'               => 'datetime',
        'dispatched_at'         => 'datetime',
        'completed_at'          => 'datetime',
    ];

    // ── Relationships ──

    public function product(): BelongsTo
    {
        return $this->belongsTo(RtdProduct::class, 'product_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(User::class, 'brand_id');
    }

    public function converter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converter_id');
    }

    public function dispatchProofs(): HasMany
    {
        return $this->hasMany(RtdDispatchProof::class, 'order_id');
    }

    public function payout(): HasOne
    {
        return $this->hasOne(RtdPayout::class, 'order_id');
    }

    // ── Scopes ──

    public function scopeRequested(Builder $query): Builder
    {
        return $query->where('status', RTDOrderStatus::REQUESTED);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            RTDOrderStatus::ACCEPTED,
            RTDOrderStatus::PAID,
            RTDOrderStatus::IN_PRODUCTION,
            RTDOrderStatus::DISPATCHED,
        ]);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', RTDOrderStatus::PAID);
    }

    public function scopeDispatched(Builder $query): Builder
    {
        return $query->where('status', RTDOrderStatus::DISPATCHED);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', RTDOrderStatus::COMPLETED);
    }
}
