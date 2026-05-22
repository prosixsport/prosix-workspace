<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderRead extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
