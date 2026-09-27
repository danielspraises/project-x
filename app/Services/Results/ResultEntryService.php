<?php

namespace App\Services\Results;

use App\Models\ResultAuditLog;
use App\Models\StudentResult;
use App\Models\StudentResultVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultEntryService
{
    public function __construct(
        private readonly ResultEntryValidator $validator,
        private readonly ResultEntryScope $scope,
    ) {
    }

    /**
     * Create a result through the single controlled result-entry path.
     *
     * Version 1 and the creation audit entry are written in the same transaction
     * as the result so the history cannot drift from the live record.
     */
    public function create(User $user, array $data): StudentResult
    {
        $data['institution_id'] ??= $user->institution_id;
        $data['status'] ??= 'draft';
        $data['entered_by'] ??= $user->id;
        $data['updated_by'] ??= $user->id;
        $data = $this->prepareCalculatedFields($data, $user->institution);
        $data = $this->validator->validate($data, (int) $user->institution_id);

        return DB::transaction(function () use ($user, $data) {
            $result = StudentResult::create($this->resultAttributes($data));

            if (! $this->scope->canEnter($user, $result)) {
                throw ValidationException::withMessages([
                    'result' => 'You are not authorised to enter results for this student and subject/course.',
                ]);
            }

            $result->refresh();

            $this->recordVersion(
                $result,
                $user,
                'created',
                $this->snapshot($result),
                'Initial result entry.'
            );

            $this->recordAudit(
                $result,
                $user,
                'result_created',
                'Initial result entry.',
                null,
                $this->snapshot($result)
            );

            return $result;
        });
    }

    /**
     * Update a result through the same controlled path.
     *
     * Published and locked results are immutable here. They must first pass
     * through the controlled correction/unlock workflow.
     */
    public function update(User $user, StudentResult $result, array $data): StudentResult
    {
        if ((int) $result->institution_id !== (int) $user->institution_id) {
            abort(403);
        }

        $this->scope->authorize($user, $result);

        if (in_array($result->status, ['published', 'locked'], true)) {
            throw ValidationException::withMessages([
                'result' => 'Published or locked results cannot be edited directly. Open a controlled correction request first.',
            ]);
        }

        $before = $this->snapshot($result);

        $data = array_merge($result->only([
            'institution_id',
            'student_id',
            'academic_session_id',
            'term_id',
            'class_id',
            'arm_id',
            'subject_offering_id',
            'course_registration_id',
            'course_offering_id',
            'ca_score',
            'exam_score',
            'total_score',
            'grade',
            'grade_point',
            'status',
        ]), $data);

        $data['institution_id'] = $result->institution_id;
        $data['updated_by'] = $user->id;
        $data = $this->prepareCalculatedFields($data, $user->institution);
        $data = $this->validator->validate($data, (int) $user->institution_id, $result);

        return DB::transaction(function () use ($user, $result, $data, $before) {
            $result->fill($this->resultAttributes($data));
            $result->save();
            $result->refresh();

            $this->recordVersion(
                $result,
                $user,
                'updated',
                $this->snapshot($result),
                'Result updated.'
            );

            $this->recordAudit(
                $result,
                $user,
                'result_updated',
                'Result updated.',
                $before,
                $this->snapshot($result)
            );

            return $result;
        });
    }

    /**
     * Move a result to the next permitted workflow state.
     *
     * Locked results remain terminal. Controlled correction/unlock must be
     * handled by the workflow layer rather than by this method.
     */
    public function transition(User $user, StudentResult $result, string $status): StudentResult
    {
        $this->scope->authorize($user, $result);

        if (! $this->validator->canTransition($result->status, $status)) {
            throw ValidationException::withMessages([
                'status' => "A result cannot move from {$result->status} to {$status}.",
            ]);
        }

        $before = $this->snapshot($result);

        return DB::transaction(function () use ($user, $result, $status, $before) {
            $result->status = $status;
            $result->updated_by = $user->id;

            $timestamp = now();

            match ($status) {
                'submitted' => $result->submitted_at ??= $timestamp,
                'approved' => $result->approved_at ??= $timestamp,
                'published' => $result->published_at ??= $timestamp,
                'locked' => $result->locked_at ??= $timestamp,
                default => null,
            };

            $result->save();
            $result->refresh();

            $this->recordVersion(
                $result,
                $user,
                'status_changed',
                $this->snapshot($result),
                "Status changed to {$status}."
            );

            $this->recordAudit(
                $result,
                $user,
                'result_status_changed',
                "Status changed to {$status}.",
                $before,
                $this->snapshot($result)
            );

            return $result;
        });
    }

    private function recordVersion(
        StudentResult $result,
        User $user,
        string $changeType,
        array $snapshot,
        ?string $reason = null
    ): StudentResultVersion {
        $next = ((int) StudentResultVersion::query()
            ->where('institution_id', $result->institution_id)
            ->where('student_result_id', $result->id)
            ->max('version_number')) + 1;

        return StudentResultVersion::create([
            'institution_id' => $result->institution_id,
            'student_result_id' => $result->id,
            'version_number' => $next,
            'changed_by' => $user->id,
            'change_type' => $changeType,
            'snapshot' => $snapshot,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    private function recordAudit(
        StudentResult $result,
        User $user,
        string $action,
        ?string $reason,
        ?array $oldValues,
        ?array $newValues
    ): ResultAuditLog {
        return ResultAuditLog::create([
            'institution_id' => $result->institution_id,
            'student_result_id' => $result->id,
            'result_submission_id' => null,
            'user_id' => $user->id,
            'action' => $action,
            'reason' => $reason,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    private function snapshot(StudentResult $result): array
    {
        return $result->only([
            'id',
            'institution_id',
            'student_id',
            'academic_session_id',
            'term_id',
            'class_id',
            'arm_id',
            'subject_offering_id',
            'course_registration_id',
            'course_offering_id',
            'ca_score',
            'exam_score',
            'total_score',
            'grade',
            'grade_point',
            'status',
            'submitted_at',
            'approved_at',
            'published_at',
            'locked_at',
            'entered_by',
            'updated_by',
        ]);
    }

    private function prepareCalculatedFields(array $data, $institution): array
    {
        $ca = array_key_exists('ca_score', $data) && is_numeric($data['ca_score']) ? (float) $data['ca_score'] : null;
        $exam = array_key_exists('exam_score', $data) && is_numeric($data['exam_score']) ? (float) $data['exam_score'] : null;

        if ($ca !== null && $exam !== null) {
            $data['total_score'] = round($ca + $exam, 2);
        } else {
            $data['total_score'] = null;
        }

        $data['grade'] = null;
        $data['grade_point'] = null;

        if ($data['total_score'] === null) {
            return $data;
        }

        foreach ($institution->gradingScale() as $row) {
            if ($data['total_score'] >= (float) $row['min_score'] && $data['total_score'] <= (float) $row['max_score']) {
                $data['grade'] = (string) $row['grade'];

                if (
                    $institution->education_level === 'tertiary'
                    && isset($row['grade_point'])
                    && is_numeric($row['grade_point'])
                ) {
                    $data['grade_point'] = (float) $row['grade_point'];
                }

                return $data;
            }
        }

        throw ValidationException::withMessages([
            'total_score' => "No grading band covers the calculated total score of {$data['total_score']}.",
        ]);
    }

    private function resultAttributes(array $data): array
    {
        return array_intersect_key(
            $data,
            array_flip([
                'institution_id',
                'student_id',
                'academic_session_id',
                'term_id',
                'class_id',
                'arm_id',
                'subject_offering_id',
                'course_registration_id',
                'course_offering_id',
                'ca_score',
                'exam_score',
                'total_score',
                'grade',
                'grade_point',
                'status',
                'submitted_at',
                'approved_at',
                'published_at',
                'locked_at',
                'entered_by',
                'updated_by',
            ])
        );
    }
}
