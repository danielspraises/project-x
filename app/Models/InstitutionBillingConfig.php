<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionBillingConfig extends Model
{
    protected $table = 'institution_billing_config';

    protected $fillable = [
        'institution_id', 'billing_type', 'rate_amount', 'per_student_rate',
        'currency', 'billing_anniversary_date', 'contract_start_date',
        'contract_end_date', 'status', 'notes',
    ];

    protected $casts = [
        'billing_anniversary_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
    ];
}
