<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketInsight extends Model
{
    use HasFactory;

    protected $fillable = [
        'insight_date',
        'insight_text',
        'sentiment',
        'articles',
    ];

    protected $casts = [
        'insight_date' => 'date',
        'articles' => 'array',
    ];
}
