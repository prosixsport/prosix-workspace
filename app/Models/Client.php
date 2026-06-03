<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
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
}
