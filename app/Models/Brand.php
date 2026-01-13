<?php

namespace App\Models;

use App\Enums\BrandStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name', // For mill brands
        'company_name', // For user brand profiles
        'brand_name',
        'contact_person_name',
        'mobile',
        'email',
        'gst',
        'city',
        'location',
        'latitude',
        'longitude',
        'status',
        'profile_complete',
    ];

    protected $casts = [
        'status' => BrandStatus::class,
        'profile_complete' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function brandTypes(): BelongsToMany
    {
        return $this->belongsToMany(BrandType::class, 'brand_brand_types');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }
}
