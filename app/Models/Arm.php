<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\SubjectOffering;
use App\Models\ClassTeacherAssignment;

class Arm extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['institution_id', 'class_id', 'name'];

    public function subjectOfferings(): HasMany
    {
        return $this->hasMany(SubjectOffering::class);
    }

    public function classTeacherAssignments(): HasMany
    {
        return $this->hasMany(ClassTeacherAssignment::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
