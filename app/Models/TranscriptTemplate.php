<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TranscriptTemplate extends Model
{
    protected $fillable = [
        'name',
        'code',
        'education_level',
        'version',
        'description',
        'view',
        'configuration',
        'is_active',
    ];

    protected $casts = [
        'version' => 'integer',
        'configuration' => 'array',
        'is_active' => 'boolean',
    ];

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class, 'institution_transcript_templates')
            ->withPivot('assigned_by', 'assigned_at')
            ->withTimestamps();
    }
}
