<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResultSubmission extends Model
{
    protected $fillable = [
        'institution_id', 'course_offering_id', 'subject_offering_id',
        'academic_session_id', 'term_id', 'submitted_by', 'status',
        'submission_note', 'submitted_at', 'returned_at', 'published_at', 'locked_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'returned_at' => 'datetime',
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function courseOffering(): BelongsTo { return $this->belongsTo(CourseOffering::class); }
    public function subjectOffering(): BelongsTo { return $this->belongsTo(SubjectOffering::class); }
    public function academicSession(): BelongsTo { return $this->belongsTo(AcademicSession::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function submittedBy(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }

    public function results(): HasMany
    {
        return $this->hasMany(StudentResult::class, 'result_submission_id');
    }

    public function verifications(): HasMany { return $this->hasMany(ResultVerification::class); }
    public function auditLogs(): HasMany { return $this->hasMany(ResultAuditLog::class); }
    public function alerts(): HasMany { return $this->hasMany(ResultAlert::class); }
}
