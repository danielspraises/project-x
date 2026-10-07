<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentScore extends Model
{
    use BelongsToInstitution;

    protected $fillable = [
        'institution_id', 'assessment_component_id', 'student_result_id',
        'score', 'is_absent', 'entered_by', 'updated_by',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'is_absent' => 'boolean',
    ];

    public function component(): BelongsTo { return $this->belongsTo(AssessmentComponent::class, 'assessment_component_id'); }
    public function studentResult(): BelongsTo { return $this->belongsTo(StudentResult::class); }
    public function enteredBy(): BelongsTo { return $this->belongsTo(User::class, 'entered_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    /**
     * True once this component has something recorded for the student —
     * either a real score or an absent mark — so the overall total can
     * complete even for a student who missed one component.
     */
    public function isEntered(): bool
    {
        return $this->is_absent || $this->score !== null;
    }
}
