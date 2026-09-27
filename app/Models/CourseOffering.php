<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseOffering extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['institution_id', 'course_id', 'term_id', 'programme_id', 'level', 'lecturer_id', 'requirement_type'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(CourseRegistration::class);
    }
}
