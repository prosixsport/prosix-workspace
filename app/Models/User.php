<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'company',
        'address',
        'password',
        'role',
        'avatar',
        'job_title',
        'is_active',
        'created_by',

        // CLIENT ACCOUNT APPROVAL
        'account_status',
        'registration_source',
        'approved_at',
        'approved_by',

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
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

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

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /*
    |--------------------------------------------------------------------------
    | Account Status Helpers
    |--------------------------------------------------------------------------
    */

    public function isAccountActive(): bool
    {
        return $this->is_active
            && $this->account_status === 'active';
    }

    public function isAccountPending(): bool
    {
        return $this->account_status === 'pending';
    }

    public function isAccountRejected(): bool
    {
        return $this->account_status === 'rejected';
    }

    public function wasAddedByAdmin(): bool
    {
        return $this->registration_source === 'admin';
    }

    public function registeredByCustomer(): bool
    {
        return $this->registration_source === 'self';
    }

    public function needsFirstPassword(): bool
    {
        return $this->isClient()
            && $this->wasAddedByAdmin()
            && is_null($this->password);
    }

    /*
    |--------------------------------------------------------------------------
    | Order Permission
    |--------------------------------------------------------------------------
    */

    public function canCreateOrders(): bool
    {
        return $this->isSuperAdmin()
            || (bool) $this->can_create_orders;
    }
}
