<?php

namespace App\Services\Results;

use App\Models\ResultCorrection;
use App\Models\ResultSubmission;
use App\Models\StudentResult;
use App\Models\StudentResultVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultCorrectionService
{
    public const REQUESTED = 'requested';
    public const EDITING = 'editing';
    public const HOD_APPROVED = 'hod_approved';
    public const ICT_APPROVED = 'ict_approved';
    public const REJECTED = 'rejected';

    public function __construct(
        private readonly ResultAuditService $audit,
    ) {
    }

    public function request(User $user, StudentResult $result, string $reason): ResultCorrection
    {
        $this->assertInstitution($user, $result);

        if (!in_array($result->status, ['published', 'locked'], true)) {
            throw ValidationException::withMessages([
                'status' => 'A correction request is only available after publication.',
            ]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A correction reason is required.']);
        }

        $open = $result->corrections()
            ->whereIn('status', [self::REQUESTED, self::EDITING, self::HOD_APPROVED])
            ->exists();

        if ($open) {
            throw ValidationException::withMessages([
                'correction' => 'This result already has an active correction request.',
            ]);
        }

        return DB::transaction(function () use ($user, $result, $reason) {
            $correction = ResultCorrection::create([
                'institution_id' => $user->institution_id,
                'student_result_id' => $result->id,
                'requested_by' => $user->id,
                'status' => self::REQUESTED,
                'reason' => $reason,
                'requested_at' => now(),
            ]);

            $this->audit->log(
                $user,
                'correction_requested',
                $result,
                $result->submission,
                null,
                ['correction_id' => $correction->id, 'status' => self::REQUESTED],
                $reason
            );

            return $correction->refresh();
        });
    }

    public function authorizeEditing(User $user, ResultCorrection $correction): ResultCorrection
    {
        $this->assertInstitution($user, $correction);

        if ($correction->status !== self::REQUESTED) {
            throw ValidationException::withMessages([
                'status' => 'Only requested corrections can be released for editing.',
            ]);
        }

        return DB::transaction(function () use ($user, $correction) {
            $old = ['status' => $correction->status];

            $correction->update(['status' => self::EDITING]);

            $result = $correction->result()->lockForUpdate()->firstOrFail();
            $result->update([
                'status' => 'returned',
                'locked_at' => null,
            ]);

            $this->audit->log(
                $user,
                'correction_released_for_editing',
                $result,
                $correction->result?->submission,
                $old,
                ['status' => self::EDITING],
                $correction->reason
            );

            return $correction->refresh();
        });
    }

    public function hodApprove(User $user, ResultCorrection $correction, ?string $note = null): ResultCorrection
    {
        return $this->review($user, $correction, self::HOD_APPROVED, $note);
    }

    public function ictApprove(User $user, ResultCorrection $correction, ?string $note = null): ResultCorrection
    {
        return $this->review($user, $correction, self::ICT_APPROVED, $note);
    }

    public function reject(User $user, ResultCorrection $correction, string $note): ResultCorrection
    {
        $this->assertInstitution($user, $correction);

        if (trim($note) === '') {
            throw ValidationException::withMessages(['note' => 'A rejection note is required.']);
        }

        if (!in_array($correction->status, [self::REQUESTED, self::EDITING, self::HOD_APPROVED], true)) {
            throw ValidationException::withMessages(['status' => 'This correction cannot be rejected in its current state.']);
        }

        return DB::transaction(function () use ($user, $correction, $note) {
            $old = ['status' => $correction->status];
            $correction->update([
                'status' => self::REJECTED,
                'resolved_by' => $user->id,
                'resolution_note' => $note,
                'resolved_at' => now(),
            ]);

            $this->audit->log(
                $user,
                'correction_rejected',
                $correction->result,
                $correction->result?->submission,
                $old,
                ['status' => self::REJECTED],
                $note
            );

            return $correction->refresh();
        });
    }

    private function review(User $user, ResultCorrection $correction, string $nextStatus, ?string $note): ResultCorrection
    {
        $this->assertInstitution($user, $correction);

        $allowed = $nextStatus === self::HOD_APPROVED
            ? [self::EDITING]
            : [self::HOD_APPROVED];

        if (!in_array($correction->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => 'The correction is not ready for this approval stage.',
            ]);
        }

        return DB::transaction(function () use ($user, $correction, $nextStatus, $note) {
            $result = $correction->result()->lockForUpdate()->firstOrFail();
            $old = ['status' => $correction->status];

            if ($nextStatus === self::ICT_APPROVED) {
                $result->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'locked_at' => now(),
                ]);
                $correction->update([
                    'status' => self::ICT_APPROVED,
                    'resolved_by' => $user->id,
                    'resolution_note' => $note,
                    'resolved_at' => now(),
                ]);
            } else {
                $correction->update([
                    'status' => self::HOD_APPROVED,
                    'resolution_note' => $note,
                ]);
            }

            $this->audit->log(
                $user,
                'correction_' . $nextStatus,
                $result,
                $correction->result?->submission,
                $old,
                ['status' => $nextStatus],
                $note
            );

            return $correction->refresh();
        });
    }

    private function assertInstitution(User $user, StudentResult|ResultCorrection $model): void
    {
        if ((int) $user->institution_id !== (int) $model->institution_id) {
            abort(403);
        }
    }
}
