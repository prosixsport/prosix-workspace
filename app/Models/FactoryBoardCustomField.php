<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactoryBoardCustomField extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'options',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
