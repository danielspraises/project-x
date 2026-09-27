<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\ResultAlert;
use App\Models\ResultSubmission;
use App\Models\SchoolClass;
use App\Models\SubjectOffering;
use App\Models\StudentResult;
use App\Models\Term;
use App\Services\Results\ResultEntryContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function __construct(
        private readonly \App\Services\Results\ResultEntryService $resultEntryService,
        private readonly ResultEntryContext $context,
    ) {
    }

    public function index(Request $request): View
    {
        $user = Auth::user();
        $institution = $user->institution;

        $sessions = AcademicSession::with('terms')
            ->where('institution_id', $user->institution_id)
            ->orderByDesc('start_date')
            ->get();

        $selectedSession = $request->integer('academic_session_id') ?: $sessions->first()?->id;
        $terms = Term::where('institution_id', $user->institution_id)
            ->where('academic_session_id', $selectedSession)
            ->orderBy('order')
            ->get();

        $selectedTerm = $request->integer('term_id') ?: $terms->first()?->id;

        $mode = $institution?->education_level === 'tertiary' ? 'tertiary' : 'basic';

        $classes = collect();
        $offerings = collect();
        $courseOfferings = collect();
        $selectedOffering = null;
        $selectedClass = null;
        $selectedCourseOffering = null;
        $students = collect();
        $existingResults = collect();

        if ($mode === 'basic' && $selectedSession && $selectedTerm) {
            $classes = SchoolClass::where('institution_id', $user->institution_id)
                ->with('arms')
                ->orderBy('order')
                ->orderBy('name')
                ->get();

            $selectedClass = $request->integer('class_id') ?: null;

            $offerings = $this->context
                ->basicFor($user, $selectedSession, $selectedTerm, $selectedClass);

            $selectedOfferingId = $request->integer('subject_offering_id') ?: null;

            if ($selectedOfferingId) {
                $selectedOffering = $offerings->firstWhere('id', $selectedOfferingId);

                if ($selectedOffering) {
                    $students = $this->context->studentsForBasic($user, $selectedOffering);
                    $existingResults = StudentResult::where('institution_id', $user->institution_id)
                        ->where('academic_session_id', $selectedSession)
                        ->where('term_id', $selectedTerm)
                        ->where('subject_offering_id', $selectedOffering->id)
                        ->whereIn('student_id', $students->pluck('id'))
                        ->get()
                        ->keyBy('student_id');
                }
            }
        }

        if ($mode === 'tertiary' && $selectedSession && $selectedTerm) {
            $courseOfferings = $this->context->tertiaryOfferingsFor($user, $selectedSession, $selectedTerm);

            $selectedCourseOfferingId = $request->integer('course_offering_id') ?: null;

            if ($selectedCourseOfferingId) {
                $selectedCourseOffering = $courseOfferings->firstWhere('id', $selectedCourseOfferingId);

                if ($selectedCourseOffering) {
                    $students = $selectedCourseOffering->registrations()
                        ->with('student')
                        ->get()
                        ->pluck('student')
                        ->filter()
                        ->values();

                    $existingResults = StudentResult::where('institution_id', $user->institution_id)
                        ->where('academic_session_id', $selectedSession)
                        ->where('term_id', $selectedTerm)
                        ->where('course_offering_id', $selectedCourseOffering->id)
                        ->whereIn('student_id', $students->pluck('id'))
                        ->get()
                        ->keyBy('student_id');

                    $selectedOffering = $selectedCourseOffering;
                }
            }
        }

        // --- Results Control Room (Phase 3) data ---

        $faculty = $request->string('faculty')->toString();
        $department = $request->string('department')->toString();
        $level = $request->string('level')->toString();
        $programme = $request->string('programme')->toString();
        $courseSearch = $request->string('course_search')->toString();

        $submissionsQuery = ResultSubmission::query()
            ->where('institution_id', $user->institution_id)
            ->when($selectedSession, fn ($q) => $q->where('academic_session_id', $selectedSession))
            ->when($selectedTerm, fn ($q) => $q->where('term_id', $selectedTerm))
            ->with([
                'courseOffering.course.department.faculty',
                'courseOffering.programme',
                'subjectOffering.subject',
                'subjectOffering.schoolClass',
                'verifications',
                'submittedBy',
            ]);

        if ($faculty !== '') {
            $submissionsQuery->whereHas(
                'courseOffering.course.department.faculty',
                fn ($q) => $q->where('name', $faculty)
            );
        }

        if ($department !== '') {
            $submissionsQuery->whereHas(
                'courseOffering.course.department',
                fn ($q) => $q->where('name', $department)
            );
        }

        if ($level !== '') {
            $submissionsQuery->whereHas(
                'courseOffering',
                fn ($q) => $q->where('level', $level)
            );
        }

        if ($programme !== '') {
            $submissionsQuery->whereHas(
                'courseOffering.programme',
                fn ($q) => $q->where('name', $programme)
            );
        }

        if ($courseSearch !== '') {
            $submissionsQuery->where(function ($q) use ($courseSearch) {
                $q->whereHas(
                    'courseOffering.course',
                    fn ($c) => $c->where('code', 'like', "%{$courseSearch}%")
                        ->orWhere('title', 'like', "%{$courseSearch}%")
                )
                ->orWhereHas(
                    'subjectOffering.subject',
                    fn ($c) => $c->where('name', 'like', "%{$courseSearch}%")
                )
                ->orWhereHas(
                    'submittedBy',
                    fn ($u) => $u->where('name', 'like', "%{$courseSearch}%")
                );
            });
        }

        $rows = $submissionsQuery->latest('updated_at')->get();

        $faculties = Faculty::where('institution_id', $user->institution_id)
            ->orderBy('name')
            ->pluck('name');

        $departments = Department::where('institution_id', $user->institution_id)
            ->orderBy('name')
            ->pluck('name');

        $levels = CourseOffering::where('institution_id', $user->institution_id)
            ->whereNotNull('level')
            ->distinct()
            ->orderBy('level')
            ->pluck('level');

        $programmes = Programme::where('institution_id', $user->institution_id)
            ->orderBy('name')
            ->pluck('name');

        $statsBase = ResultSubmission::where('institution_id', $user->institution_id)
            ->when($selectedSession, fn ($q) => $q->where('academic_session_id', $selectedSession))
            ->when($selectedTerm, fn ($q) => $q->where('term_id', $selectedTerm));

        $stats = [
            'total_courses' => (clone $statsBase)->count(),
            'submitted' => (clone $statsBase)->where('status', 'submitted')->count(),
            'pending_ict' => (clone $statsBase)
                ->where('status', 'submitted')
                ->whereHas('verifications', fn ($q) => $q->where('level', 'hod')->where('decision', 'approved'))
                ->count(),
            'published' => (clone $statsBase)->whereIn('status', ['published', 'locked'])->count(),
        ];

        $alerts = ResultAlert::where('institution_id', $user->institution_id)
            ->where('resolved', false)
            ->latest()
            ->get();

        return view('ict-admin.results.index', compact(
            'mode',
            'sessions',
            'terms',
            'classes',
            'offerings',
            'courseOfferings',
            'selectedSession',
            'selectedTerm',
            'selectedOffering',
            'selectedClass',
            'selectedCourseOffering',
            'students',
            'existingResults',
            'rows',
            'faculties',
            'departments',
            'levels',
            'programmes',
            'stats',
            'alerts'
        ));
    }

    public function store(Request $request)
    {
        $result = $this->resultEntryService->create(Auth::user(), $request->all());

        return back()->with('success', "Result #{$result->id} saved as draft.");
    }

    public function update(Request $request, StudentResult $result)
    {
        $this->resultEntryService->update(Auth::user(), $result, $request->all());

        return back()->with('success', 'Result updated successfully.');
    }

    public function transition(Request $request, StudentResult $result)
    {
        $request->validate([
            'status' => ['required', 'string', 'in:submitted,approved,published,locked'],
        ]);

        $this->resultEntryService->transition(
            Auth::user(),
            $result,
            $request->string('status')->toString()
        );

        return back()->with('success', 'Result workflow status updated successfully.');
    }
}
