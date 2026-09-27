<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassTeacherAssignment extends Model
{
    use BelongsToInstitution;

    protected $fillable = [
        'institution_id', 'academic_session_id', 'term_id', 'class_id', 'arm_id', 'teacher_id', 'scope', 'scope_key',
    ];

    public function academicSession(): BelongsTo { return $this->belongsTo(AcademicSession::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function arm(): BelongsTo { return $this->belongsTo(Arm::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(User::class, 'teacher_id'); }
    public function isClassWide(): bool { return $this->scope === 'class'; }
}
