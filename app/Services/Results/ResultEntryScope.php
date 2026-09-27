<?php

namespace App\Services\Results;

use App\Models\StudentResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ResultEntryScope
{
    public function canEnter(User $user, StudentResult $result): bool
    {
        if ($user->institution_id === null || (int) $user->institution_id !== (int) $result->institution_id) {
            return false;
        }

        if (! $user->hasPermission('results.enter') || $result->status === 'locked') {
            return false;
        }

        if ($result->subject_offering_id !== null) {
            return $this->canEnterBasicResult($user, $result);
        }

        if ($result->course_registration_id !== null) {
            return $this->canEnterTertiaryResult($user, $result);
        }

        return false;
    }

    public function authorize(User $user, StudentResult $result): void
    {
        abort_unless($this->canEnter($user, $result), 403);
    }

    private function canEnterBasicResult(User $user, StudentResult $result): bool
    {
        $offering = DB::table('subject_offerings')
            ->where('id', $result->subject_offering_id)
            ->where('institution_id', $result->institution_id)
            ->first();

        if ($offering === null
            || (int) $offering->academic_session_id !== (int) $result->academic_session_id
            || (int) $offering->term_id !== (int) $result->term_id
            || (int) $offering->class_id !== (int) $result->class_id) {
            return false;
        }

        if ($offering->arm_id !== null && (int) $offering->arm_id !== (int) $result->arm_id) {
            return false;
        }

        // A basic-education result is valid only for a student registered for
        // the selected subject offering.
        $registered = DB::table('subject_registrations')
            ->where('institution_id', $result->institution_id)
            ->where('student_id', $result->student_id)
            ->where('subject_offering_id', $result->subject_offering_id)
            ->exists();

        if (! $registered) {
            return false;
        }

        $explicitTeacherQuery = DB::table('subject_offering_teachers')
            ->where('subject_offering_id', $offering->id);

        if ($explicitTeacherQuery->exists()) {
            return $explicitTeacherQuery
                ->where('teacher_id', $user->id)
                ->exists();
        }

        $assignmentQuery = DB::table('class_teacher_assignments')
            ->where('institution_id', $result->institution_id)
            ->where('academic_session_id', $result->academic_session_id)
            ->where('term_id', $result->term_id)
            ->where('class_id', $result->class_id)
            ->where('teacher_id', $user->id);

        if ($offering->scope === 'class') {
            return $assignmentQuery->where('scope', 'class')->exists();
        }

        return $assignmentQuery
            ->where(function ($query) use ($result) {
                $query->where(function ($q) use ($result) {
                    $q->where('scope', 'arm')->where('arm_id', $result->arm_id);
                })->orWhere('scope', 'class');
            })
            ->exists();
    }

    private function canEnterTertiaryResult(User $user, StudentResult $result): bool
    {
        $registration = DB::table('course_registrations')
            ->join('course_offerings', 'course_offerings.id', '=', 'course_registrations.course_offering_id')
            ->join('terms', 'terms.id', '=', 'course_offerings.term_id')
            ->where('course_registrations.id', $result->course_registration_id)
            ->where('course_registrations.institution_id', $result->institution_id)
            ->select([
                'course_registrations.student_id',
                'course_registrations.course_offering_id',
                'course_offerings.lecturer_id',
                'course_offerings.term_id',
                'terms.academic_session_id',
                'course_offerings.institution_id',
            ])
            ->first();

        if ($registration === null
            || (int) $registration->student_id !== (int) $result->student_id
            || (int) $registration->course_offering_id !== (int) $result->course_offering_id
            || (int) $registration->term_id !== (int) $result->term_id
            || (int) $registration->academic_session_id !== (int) $result->academic_session_id
            || (int) $registration->institution_id !== (int) $result->institution_id) {
            return false;
        }

        return $registration->lecturer_id !== null
            && (int) $registration->lecturer_id === (int) $user->id;
    }
}
