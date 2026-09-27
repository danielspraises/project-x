<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use App\Models\CourseRegistration;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CourseRegistrationController extends Controller
{
    /**
     * Shows the offering's eligible students (same programme + level) with checkboxes —
     * already-registered ones pre-checked. Submitting re-syncs the full registration list
     * for this offering in one go, rather than one-student-at-a-time forms.
     */
    public function edit(CourseOffering $courseOffering): View
    {
        $this->ensureCanAssign($courseOffering);

        $eligibleStudents = Student::where('programme_id', $courseOffering->programme_id)
            ->where('level', $courseOffering->level)
            ->where('status', 'active')
            ->orderBy('last_name')
            ->get();

        $registeredStudentIds = $courseOffering->registrations()->pluck('student_id')->toArray();

        return view('ict-admin.registrations.edit', compact('courseOffering', 'eligibleStudents', 'registeredStudentIds'));
    }

    public function update(Request $request, CourseOffering $courseOffering): RedirectResponse
    {
        $this->ensureCanAssign($courseOffering);

        $validated = $request->validate([
            'student_ids' => ['array'],
            'student_ids.*' => ['exists:students,id'],
        ]);

        $eligibleStudentIds = Student::where('programme_id', $courseOffering->programme_id)
            ->where('level', $courseOffering->level)
            ->pluck('id')
            ->toArray();

        // Defense in depth: only ever register students who are actually eligible for
        // this offering, even if the submitted list was tampered with.
        $submittedIds = array_intersect($validated['student_ids'] ?? [], $eligibleStudentIds);

        DB::transaction(function () use ($courseOffering, $submittedIds) {
            $currentlyRegisteredIds = $courseOffering->registrations()->pluck('student_id')->toArray();

            $toAdd = array_diff($submittedIds, $currentlyRegisteredIds);
            $toRemove = array_diff($currentlyRegisteredIds, $submittedIds);

            foreach ($toAdd as $studentId) {
                CourseRegistration::create([
                    'institution_id' => Auth::user()->institution_id,
                    'student_id' => $studentId,
                    'course_offering_id' => $courseOffering->id,
                    'registered_by' => 'admin',
                    'registered_by_user_id' => Auth::id(),
                ]);
            }

            if (! empty($toRemove)) {
                $courseOffering->registrations()->whereIn('student_id', $toRemove)->delete();
            }
        });

        return redirect()
            ->route('ict-admin.course-offerings.registrations.edit', $courseOffering)
            ->with('success', 'Registrations updated.');
    }

    private function ensureCanAssign(CourseOffering $courseOffering): void
    {
        abort_unless(Auth::user()->hasPermission('registrations.assign'), 403);

        if (Auth::user()->isDepartmentScoped()) {
            abort_if($courseOffering->course->department_id !== Auth::user()->department_id, 403);
        }
    }
}
