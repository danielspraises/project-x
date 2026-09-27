<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultCorrection extends Model
{
    protected $fillable = [
        'institution_id', 'student_result_id', 'requested_by', 'resolved_by',
        'status', 'reason', 'resolution_note', 'requested_at', 'resolved_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function result(): BelongsTo { return $this->belongsTo(StudentResult::class, 'student_result_id'); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function resolvedBy(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by'); }
}
