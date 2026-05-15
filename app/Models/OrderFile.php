<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\Storage;

class OrderFile extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'card_type',
        'original_name',
        'file_path',
        'mime_type',
        'size',
    ];

    protected $appends = ['url'];


public function getUrlAttribute()
{
    return url('storage/' . $this->file_path);
}
    // OrderFile.php
public function user()
{
    return $this->belongsTo(\App\Models\User::class);
}
}