<?php

namespace App\Models;

use App\Enums\DealerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dealer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'profile_complete',
        'grades',
        'capacity_daily',
        'capacity_monthly',
        'capacity_unit',
    ];

    protected $casts = [
        'status' => DealerStatus::class,
        'profile_complete' => 'boolean',
        'grades' => 'array',
        'capacity_daily' => 'decimal:2',
        'capacity_monthly' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DealerLocation::class);
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'dealer_materials');
    }

    public function machines(): BelongsToMany
    {
        return $this->belongsToMany(Machine::class, 'dealer_machines');
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(DealerAcceptance::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }
}
