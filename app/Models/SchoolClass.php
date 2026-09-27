<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\SubjectOffering;
use App\Models\ClassTeacherAssignment;

class SchoolClass extends Model
{
    use BelongsToInstitution;

    protected $table = 'classes';

    protected $fillable = ['institution_id', 'name', 'code', 'order'];

    public function arms(): HasMany
    {
        return $this->hasMany(Arm::class, 'class_id');
    }

    public function subjectOfferings(): HasMany
    {
        return $this->hasMany(SubjectOffering::class, 'class_id');
    }

    public function classTeacherAssignments(): HasMany
    {
        return $this->hasMany(ClassTeacherAssignment::class, 'class_id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }
}
