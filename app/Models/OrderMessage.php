<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderMessage extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'message',
        'edited_at',
        'deleted_everyone_at',
        'deleted_for',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
        'deleted_everyone_at' => 'datetime',
        'deleted_for' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
public function reads()
{
    return $this->hasMany(OrderMessageRead::class);
}
}