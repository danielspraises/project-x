<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningContent extends Model
{
    use BelongsToInstitution;

    protected $fillable = [
        'institution_id',
        'created_by',
        'subject_offering_id',
        'course_offering_id',
        'content_type',
        'content_kind',
        'title',
        'content',
        'description',
        'status',
        'workflow_status',
        'reviewed_by',
        'reviewed_at',
        'review_remarks',
        'submitted_at',
        'approved_at',
        'published_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function subjectOffering(): BelongsTo
    {
        return $this->belongsTo(SubjectOffering::class);
    }

    public function courseOffering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(LearningContentMedia::class);
    }

    public function isPublished(): bool
    {
        return $this->workflow_status === 'published';
    }

    public function isLesson(): bool
    {
        return $this->content_type === 'lesson';
    }

    public function isLecture(): bool
    {
        return $this->content_type === 'lecture';
    }
}