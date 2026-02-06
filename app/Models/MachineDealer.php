<?php

namespace App\Models;

use App\Enums\MachineDealerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MachineDealer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'gst',
        'contact_person_name',
        'mobile',
        'email',
        'city',
        'location',
        'latitude',
        'longitude',
        'primary_machine_category',
        'primary_machine_id',
        'preferred_brand_names',
        'status',
        'profile_complete',
    ];

    protected $casts = [
        'status' => MachineDealerStatus::class,
        'profile_complete' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'preferred_brand_names' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function machineListings(): HasMany
    {
        return $this->hasMany(MachineListing::class, 'machine_dealer_id');
    }
}
