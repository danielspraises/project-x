<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningContentAudit extends Model
{
    use BelongsToInstitution;

    protected $fillable = [
        'institution_id',
        'learning_content_id',
        'user_id',
        'action',
        'from_status',
        'to_status',
        'remarks',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function learningContent(): BelongsTo
    {
        return $this->belongsTo(LearningContent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}