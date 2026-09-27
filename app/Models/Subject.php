<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Subject extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['institution_id', 'name', 'code', 'category'];

    public function offerings(): HasMany
    {
        return $this->hasMany(SubjectOffering::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }
}
