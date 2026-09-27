<?php

namespace App\Services\Results;

use App\Models\CourseOffering;
use App\Models\ResultAuditLog;
use App\Models\ResultSubmission;
use App\Models\StudentResult;
use App\Models\SubjectOffering;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultSubmissionService
{
    public function __construct(
        private readonly ResultEntryService $resultEntryService,
        private readonly ResultEntryContext $context,
    ) {
    }

    /**
     * Submit all of a lecturer's entered results for a tertiary course offering.
     *
     * Reuses an existing submission for this offering/period if one exists
     * (e.g. a previously returned submission being resubmitted) rather than
     * creating a duplicate row.
     */
    public function submitTertiary(User $user, CourseOffering $offering, ?string $note = null): ResultSubmission
    {
        if ((int) $offering->institution_id !== (int) $user->institution_id) {
            abort(403);
        }

        if ((int) $offering->lecturer_id !== (int) $user->id) {
            abort(403, 'You are not the assigned lecturer for this course offering.');
        }

        $offering->loadMissing('term');
        $academicSessionId = $offering->term->academic_session_id;
        $termId = $offering->term_id;

        $registrationIds = $offering->registrations()->pluck('id');

        if ($registrationIds->isEmpty()) {
            throw ValidationException::withMessages([
                'submission' => 'No students are registered for this course offering.',
            ]);
        }

        $results = StudentResult::where('institution_id', $user->institution_id)
            ->whereIn('course_registration_id', $registrationIds)
            ->get()
            ->keyBy('course_registration_id');

        $missing = $registrationIds->diff($results->keys());

        if ($missing->isNotEmpty() || $results->contains(fn (StudentResult $r) => $r->total_score === null)) {
            throw ValidationException::withMessages([
                'submission' => 'Every registered student must have a complete score entered before you can submit.',
            ]);
        }

        return DB::transaction(function () use ($user, $offering, $academicSessionId, $termId, $results, $note) {
            $submission = ResultSubmission::firstOrNew([
                'institution_id' => $user->institution_id,
                'course_offering_id' => $offering->id,
                'academic_session_id' => $academicSessionId,
                'term_id' => $termId,
            ]);

            $submission->fill([
                'submitted_by' => $user->id,
                'status' => 'submitted',
                'submission_note' => $note,
                'submitted_at' => now(),
                'returned_at' => null,
            ])->save();

            foreach ($results as $result) {
                if ($result->result_submission_id !== $submission->id) {
                    $result->result_submission_id = $submission->id;
                    $result->save();
                }

                if ($result->status !== 'submitted') {
                    $this->resultEntryService->transition($user, $result, 'submitted');
                }
            }

            ResultAuditLog::create([
                'institution_id' => $user->institution_id,
                'student_result_id' => null,
                'result_submission_id' => $submission->id,
                'user_id' => $user->id,
                'action' => 'submission_submitted',
                'reason' => $note,
                'old_values' => null,
                'new_values' => ['status' => 'submitted', 'result_count' => $results->count()],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $submission->fresh();
        });
    }

    /**
     * Submit all entered results for a basic-ed subject offering.
     * Mirrors submitTertiary but keyed by student_id rather than a
     * registration id, since StudentResult ties basic-ed rows to
     * (subject_offering_id, student_id) directly.
     */
    public function submitBasic(User $user, SubjectOffering $offering, ?string $note = null): ResultSubmission
    {
        if ((int) $offering->institution_id !== (int) $user->institution_id) {
            abort(403);
        }

        abort_unless(
            $this->context->canTeachBasicOffering($user, $offering),
            403,
            'You are not assigned to teach this subject offering.'
        );

        $studentIds = DB::table('subject_registrations')
            ->where('institution_id', $user->institution_id)
            ->where('subject_offering_id', $offering->id)
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            throw ValidationException::withMessages([
                'submission' => 'No students are registered for this subject offering.',
            ]);
        }

        $results = StudentResult::where('institution_id', $user->institution_id)
            ->where('subject_offering_id', $offering->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $missing = $studentIds->diff($results->keys());

        if ($missing->isNotEmpty() || $results->contains(fn (StudentResult $r) => $r->total_score === null)) {
            throw ValidationException::withMessages([
                'submission' => 'Every registered student must have a complete score entered before you can submit.',
            ]);
        }

        return DB::transaction(function () use ($user, $offering, $results, $note) {
            $submission = ResultSubmission::firstOrNew([
                'institution_id' => $user->institution_id,
                'subject_offering_id' => $offering->id,
                'academic_session_id' => $offering->academic_session_id,
                'term_id' => $offering->term_id,
            ]);

            $submission->fill([
                'submitted_by' => $user->id,
                'status' => 'submitted',
                'submission_note' => $note,
                'submitted_at' => now(),
                'returned_at' => null,
            ])->save();

            foreach ($results as $result) {
                if ($result->result_submission_id !== $submission->id) {
                    $result->result_submission_id = $submission->id;
                    $result->save();
                }

                if ($result->status !== 'submitted') {
                    $this->resultEntryService->transition($user, $result, 'submitted');
                }
            }

            ResultAuditLog::create([
                'institution_id' => $user->institution_id,
                'student_result_id' => null,
                'result_submission_id' => $submission->id,
                'user_id' => $user->id,
                'action' => 'submission_submitted',
                'reason' => $note,
                'old_values' => null,
                'new_values' => ['status' => 'submitted', 'result_count' => $results->count()],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $submission->fresh();
        });
    }
}
