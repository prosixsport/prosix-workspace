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
        'source_of_contact',
        'country',
        'city_state',
        'address',
        'price_list',
        'price_list_files',
        'lead_status',
        'team_name',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'price_list_files' => 'array',
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
