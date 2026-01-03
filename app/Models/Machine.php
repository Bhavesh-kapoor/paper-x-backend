<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
    ];

    public function dealers(): BelongsToMany
    {
        return $this->belongsToMany(Dealer::class, 'dealer_machines');
    }

    public function inquiries(): BelongsToMany
    {
        return $this->belongsToMany(Inquiry::class, 'inquiry_machines');
    }
}
