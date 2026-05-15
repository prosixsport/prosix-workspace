<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderMessageRead extends Model
{
    protected $fillable = [
        'order_message_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function message()
    {
        return $this->belongsTo(OrderMessage::class, 'order_message_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}