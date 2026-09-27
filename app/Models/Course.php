<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Course extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['institution_id', 'department_id', 'code', 'title', 'credit_unit', 'level'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(CourseOffering::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }
}
