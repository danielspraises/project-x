<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Student extends Model
{
    use BelongsToInstitution;

    protected $fillable = [
        'institution_id', 'user_id', 'admission_number', 'matric_number',
        'first_name', 'last_name', 'other_names', 'gender', 'date_of_birth',
        'phone', 'email', 'address', 'passport_photo_path',
        'department_id', 'programme_id', 'level',
        'class_id', 'arm_id', 'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function arm(): BelongsTo
    {
        return $this->belongsTo(Arm::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }

    public function subjectRegistrations(): HasMany
    {
        return $this->hasMany(SubjectRegistration::class);
    }

    public function courseRegistrations(): HasMany
    {
        return $this->hasMany(CourseRegistration::class);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->other_names} {$this->last_name}");
    }
}
