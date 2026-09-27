<?php

namespace App\Services\Results;

use App\Models\ResultAuditLog;
use App\Models\StudentResult;
use App\Models\ResultSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class ResultAuditService
{
    public function log(
        User $user,
        string $action,
        ?StudentResult $result = null,
        ?ResultSubmission $submission = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
    ): ResultAuditLog {
        return ResultAuditLog::create([
            'institution_id' => $user->institution_id,
            'student_result_id' => $result?->id,
            'result_submission_id' => $submission?->id,
            'user_id' => $user->id,
            'action' => $action,
            'reason' => $reason,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
