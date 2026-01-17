<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'category'];

    public function grades()
    {
        return $this->hasMany(MaterialGrade::class);
    }

    public function dealers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Dealer::class, 'dealer_materials');
    }

    public function inquiries(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Inquiry::class, 'inquiry_materials');
    }

    public function finishes()
    {
        return $this->hasMany(MaterialFinish::class);
    }
}
