<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SubjectOffering;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubjectRegistrationController extends Controller
{
    public function edit(SubjectOffering $subjectOffering): View
    {
        $this->ensureCanAssign();
        $subjectOffering->load(['subject', 'academicSession', 'term', 'schoolClass', 'arm', 'teachers']);

        $eligibleStudents = Student::where('status', 'active')
            ->where('class_id', $subjectOffering->class_id)
            ->when(!$subjectOffering->isClassWide(), fn ($q) => $q->where('arm_id', $subjectOffering->arm_id))
            ->orderBy('last_name')->orderBy('first_name')->get();

        $registeredStudentIds = $subjectOffering->registrations()->pluck('student_id')->all();

        return view('ict-admin.subject-registrations.edit', compact('subjectOffering', 'eligibleStudents', 'registeredStudentIds'));
    }

    public function update(Request $request, SubjectOffering $subjectOffering): RedirectResponse
    {
        $this->ensureCanAssign();
        $validated = $request->validate([
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ]);

        $studentIds = array_values(array_unique(array_map('intval', $validated['student_ids'] ?? [])));
        $validIds = Student::where('status', 'active')
            ->where('class_id', $subjectOffering->class_id)
            ->when(!$subjectOffering->isClassWide(), fn ($q) => $q->where('arm_id', $subjectOffering->arm_id))
            ->whereIn('id', $studentIds)->pluck('id')->all();

        abort_if(count($validIds) !== count($studentIds), 422, 'One or more students do not belong to this offering scope.');

        DB::transaction(function () use ($subjectOffering, $validIds) {
            $query = $subjectOffering->registrations();
            if ($validIds) {
                $query->whereNotIn('student_id', $validIds)->delete();
            } else {
                $query->delete();
            }

            $existing = $subjectOffering->registrations()->whereIn('student_id', $validIds)->pluck('student_id')->all();
            foreach (array_diff($validIds, $existing) as $studentId) {
                $subjectOffering->registrations()->create([
                    'student_id' => $studentId,
                    'registered_by' => 'admin',
                    'registered_by_user_id' => Auth::id(),
                ]);
            }
        });

        return redirect()->route('ict-admin.subject-offerings.class', ['schoolClass' => $subjectOffering->class_id, 'academic_session_id' => $subjectOffering->academic_session_id, 'term_id' => $subjectOffering->term_id])->with('success', 'Student subject registration updated.');
    }

    private function ensureCanAssign(): void
    {
        abort_unless(Auth::user()->hasPermission('subject_registrations.assign'), 403);
    }
}
