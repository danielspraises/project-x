<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectRegistration extends Model
{
    use BelongsToInstitution;

    protected $fillable = [
        'institution_id', 'student_id', 'subject_offering_id', 'registered_by', 'registered_by_user_id',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subjectOffering(): BelongsTo
    {
        return $this->belongsTo(SubjectOffering::class);
    }

    public function registeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }
}
