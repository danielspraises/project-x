<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionSetting extends Model
{
    protected $fillable = ['institution_id', 'key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];
}
