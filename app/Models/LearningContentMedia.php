<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningContentMedia extends Model
{
    protected $fillable = [
        'learning_content_id',
        'media_type',
        'source_type',
        'title',
        'path',
        'external_url',
        'mime_type',
        'size',
    ];

    public function learningContent(): BelongsTo
    {
        return $this->belongsTo(LearningContent::class);
    }

    public function isUpload(): bool
    {
        return $this->source_type === 'upload';
    }

    public function isExternal(): bool
    {
        return $this->source_type === 'external';
    }
}