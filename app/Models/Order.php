<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'po',
        'ship_date',
        'status',
        'status_color',
        'trk',
        'notes',
        'payment',
        'payment_received',
        'payment_balance',
        'created_by',
        'shipping_address',
        'packing_detail',

        // NEW
        'custom_fields',
    ];

    protected $casts = [
        'ship_date' => 'date',

        'payment_received' => 'decimal:2',
        'payment_balance' => 'decimal:2',

        // NEW
        'custom_fields' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Members / Owners
    |--------------------------------------------------------------------------
    */

    public function members()
    {
        return $this->belongsToMany(
            User::class,
            'order_members'
        )
            ->withPivot('role')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Order Members
    |--------------------------------------------------------------------------
    */

    public function orderMembers()
    {
        return $this->hasMany(
            OrderMember::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    public function messages()
    {
        return $this->hasMany(
            OrderMessage::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Files
    |--------------------------------------------------------------------------
    */

    public function files()
    {
        return $this->hasMany(
            OrderFile::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Creator
    |--------------------------------------------------------------------------
    */

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reads
    |--------------------------------------------------------------------------
    */

    public function reads()
    {
        return $this->hasMany(
            OrderRead::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Clients
    |--------------------------------------------------------------------------
    */

    public function clients()
    {
        return $this->belongsToMany(
            Client::class,
            'client_order'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Activities
    |--------------------------------------------------------------------------
    */

    public function activities()
    {
        return $this->hasMany(
            OrderActivity::class
        )->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | Work Sessions
    |--------------------------------------------------------------------------
    */

    public function workSessions()
    {
        return $this->hasMany(
            OrderWorkSession::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Active Work Session
    |--------------------------------------------------------------------------
    */

    public function activeWorkSession()
    {
        return $this->hasOne(
            OrderWorkSession::class
        )
            ->whereNull('ended_at')
            ->latestOfMany('started_at');
    }
}
