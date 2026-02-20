<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        // basic
        'name',
        'email',
        'mobile',
        'password',

        // verification
        'email_verified_at',

        // roles
        'primary_role',
        'has_secondary_role',
        'secondary_role',

        // operation area
        'operation_area',

        // company info
        'company_name',
        'gst_in',

        // location
        'state',
        'city',

        // certificates
        'udyam_certificate',
        'udyam_verified_at',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the dealer profile for this user.
     */
    public function dealer()
    {
        return $this->hasOne(Dealer::class);
    }

    /**
     * Get the brand profile for this user.
     */
    public function brand()
    {
        return $this->hasOne(Brand::class);
    }

    /**
     * Get the converter profile for this user.
     */
    public function converter()
    {
        return $this->hasOne(Converter::class);
    }

    /**
     * Get the machine dealer profile for this user.
     */
    public function machineDealer()
    {
        return $this->hasOne(MachineDealer::class);
    }

    /**
     * Get the wallet for this user.
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Role for RTD and other role-aware features (maps to primary_role).
     */
    public function getRoleAttribute(): ?string
    {
        return $this->primary_role;
    }
}
