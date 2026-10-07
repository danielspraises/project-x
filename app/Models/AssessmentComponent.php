<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentComponent extends Model
{
    use BelongsToInstitution;

    public const TYPE_CA = 'ca';
    public const TYPE_QUIZ = 'quiz';
    public const TYPE_ASSIGNMENT = 'assignment';
    public const TYPE_ATTENDANCE = 'attendance';
    public const TYPE_EXAM = 'exam';

    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_ASSIGNMENT_SYNC = 'assignment_sync';

    protected $fillable = [
        'institution_id', 'assessment_scheme_id', 'type', 'name',
        'max_score', 'order', 'source',
    ];

    public function scheme(): BelongsTo { return $this->belongsTo(AssessmentScheme::class, 'assessment_scheme_id'); }

    public function scores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }

    public function isExam(): bool
    {
        return $this->type === self::TYPE_EXAM;
    }
}
