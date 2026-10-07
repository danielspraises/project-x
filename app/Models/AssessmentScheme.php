<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentScheme extends Model
{
    use BelongsToInstitution;

    protected $fillable = [
        'institution_id', 'course_offering_id', 'subject_offering_id',
        'ca_max', 'exam_max', 'status', 'locked_at', 'created_by',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
    ];

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function courseOffering(): BelongsTo { return $this->belongsTo(CourseOffering::class); }
    public function subjectOffering(): BelongsTo { return $this->belongsTo(SubjectOffering::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function components(): HasMany
    {
        return $this->hasMany(AssessmentComponent::class)->orderBy('order');
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    public function isTertiary(): bool
    {
        return $this->course_offering_id !== null;
    }
}
