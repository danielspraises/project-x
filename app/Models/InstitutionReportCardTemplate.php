<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionReportCardTemplate extends Model
{
    protected $fillable = [
        'institution_id',
        'report_card_template_id',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportCardTemplate::class, 'report_card_template_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
