<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingChangeRequest extends Model
{
    protected $fillable = [
        'institution_id', 'requested_by', 'field_requested', 'requested_value',
        'effective_date', 'reason', 'status', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
