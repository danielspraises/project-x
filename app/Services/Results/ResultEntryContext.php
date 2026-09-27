<?php

namespace App\Services\Results;

use App\Models\AcademicSession;
use App\Models\CourseRegistration;
use App\Models\SubjectOffering;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResultEntryContext
{
    public function basicFor(User $user, int $academicSessionId, int $termId, ?int $classId = null): Collection
    {
        $query = SubjectOffering::query()
            ->with(['subject', 'schoolClass', 'arm'])
            ->where('institution_id', $user->institution_id)
            ->where('academic_session_id', $academicSessionId)
            ->where('term_id', $termId);

        if ($classId !== null) {
            $query->where('class_id', $classId);
        }

        $offerings = $query->get();

        // Only institution-wide viewers (ICT Admin via results.view) see every
        // offering unscoped. results.enter alone (lecturer/subject_teacher/
        // class_teacher all hold it) must remain scoped to the user's own
        // assignments — this used to be bypassed by this same condition.
        if ($user->hasPermission('results.view')) {
            return $offerings->values();
        }

        return $offerings->filter(function (SubjectOffering $offering) use ($user) {
            $explicit = $offering->teachers()
                ->where('users.id', $user->id)
                ->exists();

            if ($explicit) return true;
            if ($offering->teachers()->exists()) return false;

            return $this->isFallbackClassTeacher($user, $offering);
        })->values();
    }

    public function studentsForBasic(User $user, SubjectOffering $offering): Collection
    {
        if (! $this->basicOfferingIsAccessible($user, $offering)) {
            return collect();
        }

        return DB::table('subject_registrations')
            ->join('students', 'students.id', '=', 'subject_registrations.student_id')
            ->where('subject_registrations.institution_id', $user->institution_id)
            ->where('subject_registrations.subject_offering_id', $offering->id)
            ->select('students.*')
            ->orderBy('students.last_name')
            ->orderBy('students.first_name')
            ->get();
    }

    /**
     * Return tertiary course offerings visible to the Result Studio.
     * Only institution-wide viewers (ICT Admin via results.view) see every
     * offering unscoped; lecturers remain limited to their own assigned offerings.
     */
    public function tertiaryOfferingsFor(User $user, int $academicSessionId, int $termId): Collection
    {
        $query = \App\Models\CourseOffering::query()
            ->with(['course', 'programme'])
            ->where('institution_id', $user->institution_id)
            ->where('term_id', $termId)
            ->whereHas('term', fn ($q) => $q->where('academic_session_id', $academicSessionId))
            ->orderBy('level')
            ->orderBy('course_id');

        if ($user->hasPermission('results.view')) {
            return $query->get();
        }

        return $query->where('lecturer_id', $user->id)->get();
    }

    public function tertiaryFor(User $user, int $termId): Collection
    {
        return CourseRegistration::query()
            ->with(['student', 'courseOffering.course'])
            ->where('institution_id', $user->institution_id)
            ->whereHas('courseOffering', function ($query) use ($user, $termId) {
                $query->where('term_id', $termId)
                    ->where('lecturer_id', $user->id);
            })
            ->get();
    }

    /**
     * Public wrapper: can this user enter/submit results for this basic-ed
     * subject offering (explicit assignment, or class-teacher fallback)?
     * Used by ResultSubmissionService::submitBasic.
     */
    public function canTeachBasicOffering(User $user, SubjectOffering $offering): bool
    {
        return $this->basicOfferingIsAccessible($user, $offering);
    }

    /**
     * Public wrapper: is this user the class teacher (fallback assignment)
     * for this offering's class/arm/session/term — independent of whether
     * they're also the explicit subject teacher. Used by ClassTeacherController
     * to scope the approval queue, since approving is a class-teacher duty
     * even when a different person entered the actual scores.
     */
    public function isClassTeacherFor(User $user, SubjectOffering $offering): bool
    {
        return $this->isFallbackClassTeacher($user, $offering);
    }

    private function basicOfferingIsAccessible(User $user, SubjectOffering $offering): bool
    {
        if ((int) $offering->institution_id !== (int) $user->institution_id) return false;

        if ($offering->teachers()->exists()) {
            return $offering->teachers()->where('users.id', $user->id)->exists();
        }

        return $this->isFallbackClassTeacher($user, $offering);
    }

    private function isFallbackClassTeacher(User $user, SubjectOffering $offering): bool
    {
        $query = DB::table('class_teacher_assignments')
            ->where('institution_id', $user->institution_id)
            ->where('academic_session_id', $offering->academic_session_id)
            ->where('term_id', $offering->term_id)
            ->where('class_id', $offering->class_id)
            ->where('teacher_id', $user->id);

        if ($offering->scope === 'class') {
            return $query->where('scope', 'class')->exists();
        }

        return $query->where(function ($q) use ($offering) {
            $q->where(function ($arm) use ($offering) {
                $arm->where('scope', 'arm')->where('arm_id', $offering->arm_id);
            })->orWhere('scope', 'class');
        })->exists();
    }
}
