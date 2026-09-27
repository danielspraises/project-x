<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGpaSummary extends Model
{
    protected $fillable = [
        'institution_id', 'student_id', 'academic_session_id', 'term_id',
        'total_credit_units', 'total_quality_points', 'gpa', 'calculated_at',
    ];

    protected $casts = [
        'total_credit_units' => 'decimal:2',
        'total_quality_points' => 'decimal:2',
        'gpa' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function academicSession(): BelongsTo { return $this->belongsTo(AcademicSession::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
}
