<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\Student;
use App\Models\SubjectOffering;
use Illuminate\Support\Facades\Auth;

class SubjectRegistrationAutoEnroller
{
    /**
     * Register every eligible active student in this offering's class (and
     * arm, if arm-scoped) who isn't already registered. Called whenever a
     * subject offering is created, and again on update in case its
     * class/arm/scope changed (this only adds — it never removes a student
     * who no longer matches after an edit; that's a separate, pre-existing
     * gap worth a future look).
     */
    public function enrollForOffering(SubjectOffering $offering): void
    {
        if ($offering->requirement_type !== 'compulsory') {
            return; // elective offerings are a deliberate per-student choice, not automatic
        }

        $studentIds = Student::where('institution_id', $offering->institution_id)
            ->where('status', 'active')
            ->where('class_id', $offering->class_id)
            ->when(! $offering->isClassWide(), fn ($q) => $q->where('arm_id', $offering->arm_id))
            ->pluck('id');

        foreach ($studentIds as $studentId) {
            $offering->registrations()->firstOrCreate(
                ['student_id' => $studentId],
                ['registered_by' => 'admin', 'registered_by_user_id' => Auth::id()]
            );
        }
    }

    /**
     * Register a newly created (or class-assigned) student into every
     * subject offering that already exists for their class/arm in the
     * institution's current academic session and term. "Current" is
     * whatever the institution has set as its default session/term
     * (the same setting the Subject Offerings class view defaults to).
     */
    public function enrollForStudent(Student $student): void
    {
        if ($student->class_id === null) {
            return; // tertiary student, or a basic-ed student not yet placed in a class
        }

        $institution = Institution::find($student->institution_id);
        $sessionId = $institution?->setting('default_academic_session_id');
        $termId = $institution?->setting('default_term_id');

        if (! $sessionId || ! $termId) {
            return; // institution hasn't set a current session/term — nothing to enroll into
        }

        $offerings = SubjectOffering::where('institution_id', $student->institution_id)
            ->where('academic_session_id', $sessionId)
            ->where('term_id', $termId)
            ->where('class_id', $student->class_id)
            ->where('requirement_type', 'compulsory')
            ->where(function ($q) use ($student) {
                $q->where('scope', 'class')
                    ->orWhere(fn ($q2) => $q2->where('scope', 'arm')->where('arm_id', $student->arm_id));
            })
            ->get();

        foreach ($offerings as $offering) {
            $offering->registrations()->firstOrCreate(
                ['student_id' => $student->id],
                ['registered_by' => 'admin', 'registered_by_user_id' => Auth::id()]
            );
        }
    }
}
