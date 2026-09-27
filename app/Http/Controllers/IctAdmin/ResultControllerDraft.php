<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\CourseOffering;
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
            'existingResults'
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
