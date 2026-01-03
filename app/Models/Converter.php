<?php

namespace App\Models;

use App\Enums\ConverterStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Converter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'converter_type_custom',
        'capacity_daily',
        'capacity_monthly',
        'capacity_unit',
        'factory_address',
        'factory_city',
        'factory_state',
        'factory_latitude',
        'factory_longitude',
        'status',
        'profile_complete',
    ];

    protected $casts = [
        'status' => ConverterStatus::class,
        'profile_complete' => 'boolean',
        'capacity_daily' => 'decimal:2',
        'capacity_monthly' => 'decimal:2',
        'factory_latitude' => 'decimal:8',
        'factory_longitude' => 'decimal:8',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function converterTypes(): BelongsToMany
    {
        return $this->belongsToMany(ConverterType::class, 'converter_converter_types');
    }

    public function finishedProducts(): BelongsToMany
    {
        return $this->belongsToMany(FinishedProduct::class, 'converter_finished_products');
    }

    public function machines(): BelongsToMany
    {
        return $this->belongsToMany(Machine::class, 'converter_machines');
    }

    public function scrapTypes(): BelongsToMany
    {
        return $this->belongsToMany(ScrapType::class, 'converter_scrap_types');
    }

    public function rawMaterials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'converter_raw_materials');
    }
}
