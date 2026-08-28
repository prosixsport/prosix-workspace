<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactoryBoardSetting extends Model
{
    protected $fillable = [
        'auto_assign_all_owners',
        'hidden_columns',
        'column_order',
        'status_options',
        'custom_groups',
        'default_group_overrides',
    ];

    protected $casts = [
        'auto_assign_all_owners' => 'boolean',
        'hidden_columns' => 'array',
        'column_order' => 'array',
        'status_options' => 'array',
        'custom_groups' => 'array',
        'default_group_overrides' => 'array',
    ];

    public static function singleton(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'auto_assign_all_owners' => false,
                'hidden_columns' => [],
                'column_order' => [],
                'status_options' => [],
                'custom_groups' => [],
                'default_group_overrides' => [],
            ]
        );
    }
}
