<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'balance',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($wallet) {
            if (empty($wallet->wallet_id)) {
                $maxId = Wallet::max('id') ?? 0;
                $wallet->wallet_id = 'B2B-' . str_pad($maxId + 1, 5, '0', STR_PAD_LEFT) . '-WP';
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->orderBy('created_at', 'desc');
    }

    public function addCredits(float $amount, string $description, string $transactionType = 'OTHER', $referenceId = null, $referenceType = null, array $metadata = []): WalletTransaction
    {
        $this->balance += $amount;
        $this->save();

        $maxId = WalletTransaction::max('id') ?? 0;
        return $this->transactions()->create([
            'transaction_id' => 'TXN-' . str_pad($maxId + 1, 5, '0', STR_PAD_LEFT),
            'type' => 'ADDED',
            'amount' => $amount,
            'balance_after' => $this->balance,
            'description' => $description,
            'transaction_type' => $transactionType,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'metadata' => $metadata,
        ]);
    }

    public function deductCredits(float $amount, string $description, string $transactionType = 'OTHER', $referenceId = null, $referenceType = null, array $metadata = []): ?WalletTransaction
    {
        if ($this->balance < $amount) {
            return null; // Insufficient balance
        }

        $this->balance -= $amount;
        $this->save();

        $maxId = WalletTransaction::max('id') ?? 0;
        return $this->transactions()->create([
            'transaction_id' => 'TXN-' . str_pad($maxId + 1, 5, '0', STR_PAD_LEFT),
            'type' => 'DEDUCTED',
            'amount' => -$amount,
            'balance_after' => $this->balance,
            'description' => $description,
            'transaction_type' => $transactionType,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'metadata' => $metadata,
        ]);
    }
}
