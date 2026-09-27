<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Programme extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['institution_id', 'department_id', 'name', 'code', 'duration_years'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }
}
