<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicSession extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['institution_id', 'name', 'start_date', 'end_date', 'status'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class)->orderBy('order');
    }
}
