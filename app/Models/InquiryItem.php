<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InquiryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inquiry_id',
        'material_id',
        'material_category',
        'finish_coating',
        'thickness_gsm',
        'thickness_mm',
        'thickness_unit',
        'thickness_tolerance_percent',
        'thickness_tolerance_absolute',
        'quantity',
        'quantity_unit',
        'additional_specs',
        'sort_order',
    ];

    protected $casts = [
        'thickness_gsm' => 'decimal:2',
        'thickness_mm' => 'decimal:3',
        'thickness_tolerance_percent' => 'decimal:2',
        'thickness_tolerance_absolute' => 'decimal:3',
        'quantity' => 'decimal:2',
        'additional_specs' => 'array',
        'sort_order' => 'integer',
    ];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Calculate tolerance range for GSM
     */
    public function getThicknessGsmMin(): ?float
    {
        if (!$this->thickness_gsm) {
            return null;
        }
        return $this->thickness_gsm * (1 - ($this->thickness_tolerance_percent / 100));
    }

    public function getThicknessGsmMax(): ?float
    {
        if (!$this->thickness_gsm) {
            return null;
        }
        return $this->thickness_gsm * (1 + ($this->thickness_tolerance_percent / 100));
    }

    /**
     * Calculate tolerance range for MM
     */
    public function getThicknessMmMin(): ?float
    {
        if (!$this->thickness_mm) {
            return null;
        }
        return $this->thickness_mm - $this->thickness_tolerance_absolute;
    }

    public function getThicknessMmMax(): ?float
    {
        if (!$this->thickness_mm) {
            return null;
        }
        return $this->thickness_mm + $this->thickness_tolerance_absolute;
    }
}
