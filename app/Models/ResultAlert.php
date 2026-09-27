<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultAlert extends Model
{
    protected $fillable = [
        'institution_id', 'result_submission_id', 'type', 'severity',
        'title', 'message', 'resolved', 'resolved_by', 'resolved_at',
    ];

    protected $casts = [
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function submission(): BelongsTo { return $this->belongsTo(ResultSubmission::class, 'result_submission_id'); }
    public function resolvedBy(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by'); }
}
