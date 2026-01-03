<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\InquiryIntent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Inquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'poster_id',
        'poster_type',
        'title',
        'description',
        'status',
        'urgency',
        'inquiry_type',
        'intent',
        'quantity',
        'quantity_unit',
        'size',
        'price',
        'price_unit',
        'price_negotiable',
        'approx_price_note',
        'machine_condition',
        'machine_listing_id',
        'job_type',
        'timeline_days',
        'latitude',
        'longitude',
        'location',
        'specs',
        'attachments',
        'attachment_paths',
        'deadline',
        'posting_fee_paid',
        'posting_fee_amount',
    ];

    protected $casts = [
        'status' => InquiryStatus::class,
        'inquiry_type' => InquiryType::class,
        'intent' => InquiryIntent::class,
        'urgency' => 'string',
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'price_negotiable' => 'boolean',
        'posting_fee_paid' => 'boolean',
        'posting_fee_amount' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'specs' => 'array',
        'attachments' => 'array',
        'attachment_paths' => 'array',
        'deadline' => 'datetime',
        'timeline_days' => 'integer',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function machineListing(): BelongsTo
    {
        return $this->belongsTo(MachineListing::class);
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'inquiry_materials');
    }

    public function machines(): BelongsToMany
    {
        return $this->belongsToMany(Machine::class, 'inquiry_machines');
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(DealerAcceptance::class);
    }

    public function session(): HasOne
    {
        return $this->hasOne(MatchingSession::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }
}
