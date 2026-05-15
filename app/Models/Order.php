<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
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
    ];

    protected $casts = [
        'ship_date' => 'date',
        'payment_received' => 'decimal:2',
        'payment_balance' => 'decimal:2',
    ];

    public function members()
    {
        return $this->belongsToMany(User::class, 'order_members')
            ->withPivot('role')
            ->withTimestamps();
    }
public function orderMembers()
{
    return $this->hasMany(OrderMember::class);
}
    public function messages()
    {
        return $this->hasMany(OrderMessage::class);
    }
public function files()
{
    return $this->hasMany(\App\Models\OrderFile::class);
}

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

