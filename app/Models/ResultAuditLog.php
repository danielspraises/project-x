<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultAuditLog extends Model
{
    protected $fillable = [
        'institution_id', 'student_result_id', 'result_submission_id',
        'user_id', 'action', 'reason', 'old_values', 'new_values',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function result(): BelongsTo { return $this->belongsTo(StudentResult::class, 'student_result_id'); }
    public function submission(): BelongsTo { return $this->belongsTo(ResultSubmission::class, 'result_submission_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
