<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Term extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['institution_id', 'academic_session_id', 'name', 'order', 'start_date', 'end_date', 'status'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }
}
