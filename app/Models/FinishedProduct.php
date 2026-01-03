<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FinishedProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'sort_order',
    ];

    public function converters(): BelongsToMany
    {
        return $this->belongsToMany(Converter::class, 'converter_finished_products');
    }
}
