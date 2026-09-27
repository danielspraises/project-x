<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class SubjectOffering extends Model
{
    use BelongsToInstitution;

    protected $fillable = [
        'institution_id', 'academic_session_id', 'term_id', 'class_id', 'arm_id', 'subject_id',
        'scope', 'scope_key', 'requirement_type',
    ];

    public function academicSession(): BelongsTo { return $this->belongsTo(AcademicSession::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function arm(): BelongsTo { return $this->belongsTo(Arm::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subject_offering_teachers', 'subject_offering_id', 'teacher_id')
            ->withPivot('role')->withTimestamps();
    }
    public function registrations(): HasMany { return $this->hasMany(SubjectRegistration::class); }

    public function isClassWide(): bool { return $this->scope === 'class'; }

    /** Explicit teachers, otherwise the appropriate class-level/arm-level default teacher. */
    public function effectiveTeachers()
    {
        if ($this->relationLoaded('teachers') && $this->teachers->isNotEmpty()) {
            return $this->teachers;
        }
        if (! $this->academic_session_id || ! $this->term_id || ! $this->class_id) return collect();
        if (Auth::user()?->institution?->setting('subject_teacher_fallback', 'enabled') === 'disabled') return collect();

        $query = ClassTeacherAssignment::query()
            ->where('academic_session_id', $this->academic_session_id)
            ->where('term_id', $this->term_id)
            ->where('class_id', $this->class_id)
            ->with('teacher');

        if ($this->isClassWide()) {
            $assignment = $query->where('scope', 'class')->first();
        } else {
            $assignment = $query->where(function ($q) {
                $q->where(fn ($q) => $q->where('scope', 'arm')->where('arm_id', $this->arm_id))
                  ->orWhere('scope', 'class');
            })->orderByRaw("CASE WHEN scope = 'arm' THEN 0 ELSE 1 END")->first();
        }

        return $assignment?->teacher ? collect([$assignment->teacher]) : collect();
    }
}
