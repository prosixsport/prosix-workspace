<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactoryBoardSetting extends Model
{
    protected $fillable = [
        'auto_assign_all_owners',
        'hidden_columns',
    ];

    protected $casts = [
        'auto_assign_all_owners' => 'boolean',
        'hidden_columns' => 'array',
    ];

    public static function singleton(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'auto_assign_all_owners' => false,
                'hidden_columns' => [],
            ]
        );
    }
}
