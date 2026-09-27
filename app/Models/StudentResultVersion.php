<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentResultVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'institution_id',
        'student_result_id',
        'version_number',
        'changed_by',
        'change_type',
        'snapshot',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'created_at' => 'datetime',
    ];

    public function result()
    {
        return $this->belongsTo(StudentResult::class, 'student_result_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
