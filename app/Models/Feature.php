<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $fillable = ['key', 'name', 'description', 'category', 'is_core'];

    protected $casts = [
        'is_core' => 'boolean',
    ];
}
