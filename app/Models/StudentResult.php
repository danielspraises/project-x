<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentResult extends Model
{
    protected $fillable = [
        'institution_id', 'student_id', 'academic_session_id', 'term_id', 'class_id', 'arm_id',
        'subject_offering_id', 'course_registration_id', 'course_offering_id', 'result_submission_id',
        'ca_score', 'exam_score', 'total_score', 'grade', 'grade_point', 'status',
        'submitted_at', 'approved_at', 'published_at', 'locked_at', 'entered_by', 'updated_by',
    ];

    protected $casts = [
        'ca_score' => 'decimal:2',
        'exam_score' => 'decimal:2',
        'total_score' => 'decimal:2',
        'grade_point' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function academicSession(): BelongsTo { return $this->belongsTo(AcademicSession::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function class(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function arm(): BelongsTo { return $this->belongsTo(Arm::class); }
    public function subjectOffering(): BelongsTo { return $this->belongsTo(SubjectOffering::class); }
    public function courseRegistration(): BelongsTo { return $this->belongsTo(CourseRegistration::class); }
    public function courseOffering(): BelongsTo { return $this->belongsTo(CourseOffering::class); }
    public function submission(): BelongsTo { return $this->belongsTo(ResultSubmission::class, 'result_submission_id'); }
    public function enteredBy(): BelongsTo { return $this->belongsTo(User::class, 'entered_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    public function versions(): HasMany
    {
        return $this->hasMany(StudentResultVersion::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(ResultCorrection::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ResultAuditLog::class);
    }
}
