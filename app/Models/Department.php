<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Department extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['institution_id', 'faculty_id', 'name', 'code'];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }
}
