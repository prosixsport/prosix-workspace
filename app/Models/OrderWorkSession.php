<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderWorkSession extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'started_at',
        'last_seen_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
