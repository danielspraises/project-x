<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseRegistration extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['institution_id', 'student_id', 'course_offering_id', 'registered_by', 'registered_by_user_id'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function courseOffering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class);
    }
}
