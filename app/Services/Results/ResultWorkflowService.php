<?php

namespace App\Services\Results;

use App\Models\ResultSubmission;
use App\Models\ResultVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultWorkflowService
{
    public const LEVEL_HOD = 'hod';
    public const LEVEL_CLASS_TEACHER = 'class_teacher';
    public const LEVEL_ICT = 'ict';

    public const DECISION_APPROVED = 'approved';
    public const DECISION_REJECTED = 'rejected';

    public function __construct(private readonly ResultAuditService $audit)
    {
    }

    public function submit(User $user, ResultSubmission $submission, ?string $note = null): ResultSubmission
    {
        $this->assertInstitution($user, $submission);

        if (!in_array($submission->status, ['draft', 'returned'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only draft or returned submissions can be submitted.',
            ]);
        }

        if (!$submission->results()->exists()) {
            throw ValidationException::withMessages([
                'results' => 'A submission must contain at least one student result.',
            ]);
        }

        return DB::transaction(function () use ($user, $submission, $note) {
            $old = $submission->only(['status', 'submission_note', 'submitted_at']);

            $submission->update([
                'status' => 'submitted',
                'submission_note' => $note,
                'submitted_by' => $user->id,
                'submitted_at' => now(),
                'returned_at' => null,
            ]);

            $this->syncResultStatuses($submission, 'submitted');

            $this->audit->log(
                $user,
                'submitted',
                null,
                $submission,
                $old,
                $submission->only(['status', 'submission_note', 'submitted_at'])
            );

            return $submission->refresh();
        });
    }

    public function review(
        User $user,
        ResultSubmission $submission,
        string $level,
        string $decision,
        ?string $notes = null
    ): ResultSubmission {
        $this->assertInstitution($user, $submission);

        if (!in_array($level, [self::LEVEL_HOD, self::LEVEL_CLASS_TEACHER, self::LEVEL_ICT], true)) {
            throw ValidationException::withMessages(['level' => 'Invalid verification level.']);
        }

        if (!in_array($decision, [self::DECISION_APPROVED, self::DECISION_REJECTED], true)) {
            throw ValidationException::withMessages(['decision' => 'Invalid verification decision.']);
        }

        if (!in_array($submission->status, ['submitted', 'returned'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only submitted or returned submissions can be reviewed.',
            ]);
        }

        if ($decision === self::DECISION_REJECTED && trim((string) $notes) === '') {
            throw ValidationException::withMessages([
                'notes' => 'A rejection note is required.',
            ]);
        }

        if ($level === self::LEVEL_ICT && $decision === self::DECISION_APPROVED) {
            $this->assertPriorAcademicReview($submission);
        }

        return DB::transaction(function () use ($user, $submission, $level, $decision, $notes) {
            ResultVerification::create([
                'institution_id' => $user->institution_id,
                'result_submission_id' => $submission->id,
                'reviewer_id' => $user->id,
                'level' => $level,
                'decision' => $decision,
                'notes' => $notes,
                'reviewed_at' => now(),
            ]);

            $old = $submission->only(['status', 'published_at', 'locked_at']);

            if ($decision === self::DECISION_REJECTED) {
                $submission->update([
                    'status' => 'returned',
                    'returned_at' => now(),
                ]);
                $this->syncResultStatuses($submission, 'returned');
            } elseif ($level === self::LEVEL_ICT) {
                $submission->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'locked_at' => now(),
                ]);
                $this->syncResultStatuses($submission, 'published');
            } else {
                // Academic approval is recorded in result_verifications.
                // The submission remains submitted until ICT audit/publishing.
                $this->syncResultStatuses($submission, 'approved');
            }

            $this->audit->log(
                $user,
                "{$level}_{$decision}",
                null,
                $submission,
                $old,
                $submission->only(['status', 'published_at', 'locked_at']),
                $notes
            );

            return $submission->refresh();
        });
    }

    public function unlock(User $user, ResultSubmission $submission, string $reason): ResultSubmission
    {
        $this->assertInstitution($user, $submission);

        if (!in_array($submission->status, ['published', 'locked'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only published or locked submissions can be unlocked.',
            ]);
        }

        throw ValidationException::withMessages([
            'status' => 'Published submissions are locked at submission level. Use a student correction request for controlled post-publication edits.',
        ]);
    }

    private function assertPriorAcademicReview(ResultSubmission $submission): void
    {
        $approved = $submission->verifications()
            ->whereIn('level', [self::LEVEL_HOD, self::LEVEL_CLASS_TEACHER])
            ->where('decision', self::DECISION_APPROVED)
            ->exists();

        if (!$approved) {
            throw ValidationException::withMessages([
                'verification' => 'ICT publication requires prior HOD or class-teacher approval.',
            ]);
        }
    }

    private function syncResultStatuses(ResultSubmission $submission, string $status): void
    {
        $now = now();

        $updates = ['status' => $status];

        if ($status === 'submitted') {
            $updates['submitted_at'] = $now;
        } elseif ($status === 'approved') {
            $updates['approved_at'] = $now;
        } elseif ($status === 'returned') {
            $updates['approved_at'] = null;
        } elseif ($status === 'published') {
            $updates['published_at'] = $now;
            $updates['locked_at'] = $now;
            $updates['approved_at'] = $now;
        }

        $submission->results()->update($updates);
    }

    private function assertInstitution(User $user, ResultSubmission $submission): void
    {
        if ((int) $user->institution_id !== (int) $submission->institution_id) {
            abort(403);
        }
    }
}
