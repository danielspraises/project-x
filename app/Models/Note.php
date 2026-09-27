<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Note extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['institution_id', 'user_id', 'notable_type', 'notable_id', 'body'];

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
