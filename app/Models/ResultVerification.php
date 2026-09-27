<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultVerification extends Model
{
    protected $fillable = [
        'institution_id', 'result_submission_id', 'reviewer_id',
        'level', 'decision', 'notes', 'reviewed_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function submission(): BelongsTo { return $this->belongsTo(ResultSubmission::class, 'result_submission_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }
}
