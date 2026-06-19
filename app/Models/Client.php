<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'company',
        'address',
        'status',
        'created_by',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
    public function orders()
{
    return $this->belongsToMany(Order::class, 'client_order');
}
public function user()
{
    return $this->belongsTo(User::class);
}
}
