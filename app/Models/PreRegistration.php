<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PreRegistration extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'mobile',
        'full_name',
        'email',
        'company_name',
        'primary_role',
        'notes',
        'ip_address',
        'user_agent',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'user_id',
        'consumed_at',
        'ignored_at',
    ];

    protected $casts = [
        'consumed_at' => 'datetime',
        'ignored_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query
            ->whereNull('consumed_at')
            ->whereNull('ignored_at');
    }
}

