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
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
        'requirement_type', // Packaging, Printing, Packaging + Printing, Corporate Gifting / Stationery
        'packaging_type', // Conditional, shown only when posting requirement
        'quantity',
        'quantity_unit',
        'quantity_range', // Range in pieces (e.g., "1000-5000", "5000-10000")
        'size',
        'size_unit', // inches, cm, mm
        'price',
        'price_unit',
        'price_negotiable',
        'approx_price_note',
        'thickness',
        'thickness_unit',
        'machine_condition',
        'machine_listing_id',
        'job_type',
        'timeline_days',
        'timeline', // Emergency (Urgent), 3-5 Days, Flexible
        'special_needs', // Any special needs text
        'latitude',
        'longitude',
        'location',
        'location_source', // saved, manual
        'location_id', // Foreign key to dealer_locations
        'visibility', // dealers, converters, all
        'specs',
        'attachments',
        'attachment_paths',
        'design_attachments', // Photos/videos/design ideas (array of file paths)
        'reference_image', // Optional single brand reference image (relative path)
        'deadline',
        'posting_fee_paid',
        'posting_fee_amount',
        // Visibility fields
        'posted_at',
        'matching_started_at',
        'responses_received_at',
        'locked_at',
        'expires_at',
        'cooldown_until',
        'is_visible_to_dealers',
        'is_visible_to_brand',
        'hide_brand_identity',
        'hide_exact_location',
        'republish_count',
        'last_republished_at',
        'matched_dealers_count',
        'responses_count',
        'selected_dealers_count',
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
        'design_attachments' => 'array',
        'deadline' => 'datetime',
        'timeline_days' => 'integer',
        // Visibility casts
        'posted_at' => 'datetime',
        'matching_started_at' => 'datetime',
        'responses_received_at' => 'datetime',
        'locked_at' => 'datetime',
        'expires_at' => 'datetime',
        'cooldown_until' => 'datetime',
        'is_visible_to_dealers' => 'boolean',
        'is_visible_to_brand' => 'boolean',
        'hide_brand_identity' => 'boolean',
        'hide_exact_location' => 'boolean',
        'republish_count' => 'integer',
        'last_republished_at' => 'datetime',
        'matched_dealers_count' => 'integer',
        'responses_count' => 'integer',
        'selected_dealers_count' => 'integer',
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

    public function finishes(): BelongsToMany
    {
        return $this->belongsToMany(MaterialFinish::class, 'inquiry_finishes', 'inquiry_id', 'finish_id');
    }

    public function dealerLocation(): BelongsTo
    {
        return $this->belongsTo(DealerLocation::class, 'location_id');
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

    public function poster(): MorphTo
    {
        return $this->morphTo('poster', 'poster_type', 'poster_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InquiryItem::class);
    }

    public function matchmakingLogs(): HasMany
    {
        return $this->hasMany(MatchmakingLog::class);
    }

    // Visibility Scopes
    public function scopeVisibleToDealers($query)
    {
        return $query->where('is_visible_to_dealers', true)
            ->where('status', '!=', InquiryStatus::DRAFT)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeVisibleToDealer($query, $dealerId)
    {
        return $query->visibleToDealers()
            ->whereHas('matchmakingLogs', function ($q) use ($dealerId) {
                $q->where('dealer_id', $dealerId)
                    ->where('is_visible', true);
            });
    }

    public function scopeVisibleToBrand($query, $brandId)
    {
        return $query->where('poster_id', $brandId)
            ->where('poster_type', 'brand')
            ->where('is_visible_to_brand', true);
    }

    public function scopeNotLocked($query)
    {
        return $query->whereNull('locked_at')
            ->where('status', '!=', InquiryStatus::LOCKED)
            ->where('status', '!=', InquiryStatus::CHAT_ACTIVE);
    }

    public function scopeCanRepublish($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('cooldown_until')
                ->orWhere('cooldown_until', '<=', now());
        })
        ->whereIn('status', [
            InquiryStatus::DEAL_FAILED,
            InquiryStatus::EXPIRED,
            InquiryStatus::DEAL_SUCCESS,
        ]);
    }

    /**
     * Get sanitized data for dealer view (without brand identity)
     */
    public function getDealerViewData(): array
    {
        $data = $this->toArray();
        
        if ($this->hide_brand_identity) {
            unset($data['brand_id']);
            unset($data['poster_id']);
            $data['brand_name'] = null;
        }
        
        if ($this->hide_exact_location) {
            // Show only city, not exact coordinates
            $data['latitude'] = null;
            $data['longitude'] = null;
            if ($this->location) {
                // Extract city from location if it contains more details
                $locationParts = explode(',', $this->location);
                $data['location'] = trim(end($locationParts)); // Last part is usually city
            }
        }
        
        return $data;
    }
}
