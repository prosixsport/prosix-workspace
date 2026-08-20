<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'avatar',
        'job_title',
        'is_active',
        'created_by',

        // PROFILE
        'profile_photo',
        'about',
        'fcm_token',

        // ORDER PERMISSION
        'can_create_orders',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'can_create_orders' => 'boolean',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    /*
    |--------------------------------------------------------------------------
    | Profile Photo URL
    |--------------------------------------------------------------------------
    */

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (!$this->profile_photo) {
            return null;
        }

        return asset('storage/' . ltrim($this->profile_photo, '/'));
    }

    /*
    |--------------------------------------------------------------------------
    | Role Helpers
    |--------------------------------------------------------------------------
    */

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    /*
    |--------------------------------------------------------------------------
    | Order Permission
    |--------------------------------------------------------------------------
    */

    public function canCreateOrders(): bool
    {
        return $this->role === 'super_admin'
            || (bool) $this->can_create_orders;
    }
}
