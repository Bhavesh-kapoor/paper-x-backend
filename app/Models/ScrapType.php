<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ScrapType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'description',
        'sort_order',
    ];

    public function converters(): BelongsToMany
    {
        return $this->belongsToMany(Converter::class, 'converter_scrap_types');
    }
}
