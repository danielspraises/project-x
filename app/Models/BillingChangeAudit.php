<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingChangeAudit extends Model
{
    protected $table = 'billing_change_audit';

    protected $fillable = [
        'institution_id', 'changed_by', 'field_changed', 'old_value', 'new_value', 'reason',
    ];
}
