<?php

namespace App\Services\Results;

use App\Models\StudentResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultEntryValidator
{
    private const STATUSES = ['draft', 'submitted', 'approved', 'published', 'locked'];

    public function validate(array $data, int $institutionId, ?StudentResult $existing = null): array
    {
        $errors = [];

        $this->requireInstitution($data, $institutionId, $errors);
        $this->validateScores($data, $errors);
        $this->validateStatus($data, $existing, $errors);
        $this->validateContext($data, $institutionId, $errors);

        if ($existing === null) {
            $this->validateDuplicate($data, $institutionId, $errors);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    public function canTransition(?string $from, string $to): bool
    {
        if (! in_array($to, self::STATUSES, true)) {
            return false;
        }

        if ($from === null || $from === $to) {
            return true;
        }

        return in_array($to, [
            'draft' => ['submitted'],
            'submitted' => ['draft', 'approved'],
            'approved' => ['published'],
            'published' => ['locked'],
            'locked' => [],
        ][$from] ?? [], true);
    }

    private function requireInstitution(array $data, int $institutionId, array &$errors): void
    {
        if ((int) ($data['institution_id'] ?? 0) !== $institutionId) {
            $errors['institution_id'][] = 'The result does not belong to the current institution.';
        }
    }

    private function validateScores(array $data, array &$errors): void
    {
        foreach (['ca_score', 'exam_score', 'total_score', 'grade_point'] as $field) {
            if (
                array_key_exists($field, $data)
                && $data[$field] !== null
                && ! is_numeric($data[$field])
            ) {
                $errors[$field][] = 'The value must be numeric or null.';
            }
        }

        foreach (['ca_score', 'exam_score', 'total_score'] as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $value = (float) $data[$field];

                if ($value < 0 || $value > 100) {
                    $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be between 0 and 100.';
                }
            }
        }

        if (
            isset($data['ca_score'], $data['exam_score'], $data['total_score'])
            && is_numeric($data['ca_score'])
            && is_numeric($data['exam_score'])
            && is_numeric($data['total_score'])
            && abs(
                round((float) $data['ca_score'] + (float) $data['exam_score'], 2)
                - (float) $data['total_score']
            ) > 0.01
        ) {
            $errors['total_score'][] = 'Total score must equal CA score plus exam score.';
        }

        if (
            isset($data['grade_point'])
            && is_numeric($data['grade_point'])
            && (float) $data['grade_point'] < 0
        ) {
            $errors['grade_point'][] = 'Grade point cannot be negative.';
        }
    }

    private function validateStatus(array $data, ?StudentResult $existing, array &$errors): void
    {
        $status = $data['status'] ?? ($existing?->status ?? 'draft');

        if (! in_array($status, self::STATUSES, true)) {
            $errors['status'][] = 'Invalid result status.';
            return;
        }

        if (
            $existing !== null
            && ! $this->canTransition($existing->status, $status)
        ) {
            $errors['status'][] = "A result cannot move from {$existing->status} to {$status}.";
        }
    }

    private function validateContext(array $data, int $institutionId, array &$errors): void
    {
        $subjectOfferingId = $data['subject_offering_id'] ?? null;
        $courseRegistrationId = $data['course_registration_id'] ?? null;
        $courseOfferingId = $data['course_offering_id'] ?? null;

        $hasBasic = $subjectOfferingId !== null;
        $hasTertiary = $courseRegistrationId !== null || $courseOfferingId !== null;

        if ($hasBasic === $hasTertiary) {
            $errors['context'][] = 'A result must contain exactly one subject or tertiary course context.';
        }

        if ($hasTertiary && $courseRegistrationId === null) {
            $errors['course_registration_id'][] = 'Tertiary results must be tied to a course registration.';
        }

        if ($hasBasic) {
            if (($data['class_id'] ?? null) === null || ($data['arm_id'] ?? null) === null) {
                $errors['context'][] = 'Basic-education results require class and arm context.';
            }

            if (($data['grade_point'] ?? null) !== null) {
                $errors['grade_point'][] = 'Grade points are only valid for tertiary results.';
            }

            $offering = DB::table('subject_offerings')
                ->where('id', $subjectOfferingId)
                ->where('institution_id', $institutionId)
                ->first();

            if ($offering === null) {
                $errors['subject_offering_id'][] = 'The selected subject offering is invalid for this institution.';
            } else {
                if (
                    (int) $offering->academic_session_id !== (int) ($data['academic_session_id'] ?? 0)
                    || (int) $offering->term_id !== (int) ($data['term_id'] ?? 0)
                    || (int) $offering->class_id !== (int) ($data['class_id'] ?? 0)
                ) {
                    $errors['context'][] = 'The subject offering does not match the selected academic context.';
                }

                if (
                    $offering->arm_id !== null
                    && (int) $offering->arm_id !== (int) ($data['arm_id'] ?? 0)
                ) {
                    $errors['arm_id'][] = 'The selected arm does not match the subject offering.';
                }

                if (
                    ! DB::table('subject_registrations')
                        ->where('institution_id', $institutionId)
                        ->where('student_id', $data['student_id'] ?? 0)
                        ->where('subject_offering_id', $subjectOfferingId)
                        ->exists()
                ) {
                    $errors['student_id'][] = 'The student is not registered for the selected subject offering.';
                }
            }
        }

        if ($hasTertiary) {
            if (($data['class_id'] ?? null) !== null || ($data['arm_id'] ?? null) !== null) {
                $errors['context'][] = 'Tertiary results cannot contain class or arm context.';
            }

            $registration = DB::table('course_registrations')
                ->join('course_offerings', 'course_offerings.id', '=', 'course_registrations.course_offering_id')
                ->join('terms', 'terms.id', '=', 'course_offerings.term_id')
                ->where('course_registrations.id', $courseRegistrationId)
                ->where('course_registrations.institution_id', $institutionId)
                ->select([
                    'course_registrations.student_id',
                    'course_registrations.course_offering_id',
                    'course_offerings.term_id',
                    'terms.academic_session_id',
                ])
                ->first();

            if ($registration === null) {
                $errors['course_registration_id'][] = 'The selected course registration is invalid for this institution.';
            } else {
                if ((int) $registration->student_id !== (int) ($data['student_id'] ?? 0)) {
                    $errors['student_id'][] = 'The student does not match the course registration.';
                }

                if ((int) $registration->course_offering_id !== (int) ($courseOfferingId ?? 0)) {
                    $errors['course_offering_id'][] = 'The course offering does not match the course registration.';
                }

                if (
                    (int) $registration->term_id !== (int) ($data['term_id'] ?? 0)
                    || (int) $registration->academic_session_id !== (int) ($data['academic_session_id'] ?? 0)
                ) {
                    $errors['context'][] = 'The course registration does not match the selected academic context.';
                }
            }
        }
    }

    private function validateDuplicate(array $data, int $institutionId, array &$errors): void
    {
        $query = StudentResult::query()
            ->where('institution_id', $institutionId)
            ->where('student_id', $data['student_id'] ?? 0)
            ->where('academic_session_id', $data['academic_session_id'] ?? 0)
            ->where('term_id', $data['term_id'] ?? 0);

        if (($data['subject_offering_id'] ?? null) !== null) {
            $query->where('subject_offering_id', $data['subject_offering_id']);
        } else {
            $query->where('course_registration_id', $data['course_registration_id'] ?? 0);
        }

        if ($query->exists()) {
            $errors['result'][] = 'A result already exists for this student, academic period and subject/course context.';
        }
    }
}
