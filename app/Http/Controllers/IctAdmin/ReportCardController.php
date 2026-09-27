<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Term;
use App\Services\Results\ReportCardService;
use Illuminate\Http\Request;

class ReportCardController extends Controller
{
    public function __construct(
        private readonly ReportCardService $reportCards,
    ) {
    }

    public function index(Request $request)
    {
        $institution = $request->user()->institution;

        abort_unless($institution instanceof Institution, 403);

        if (! $institution->hasFeature('results.report_cards')) {
            abort(403, 'Report card generation is not enabled for this institution.');
        }

        $sessions = AcademicSession::query()
            ->where('institution_id', $institution->id)
            ->orderByDesc('id')
            ->get();

        $terms = Term::query()
            ->where('institution_id', $institution->id)
            ->orderByDesc('id')
            ->get();

        $students = Student::query()
            ->where('institution_id', $institution->id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(200)
            ->get();

        return view('ict-admin.results.report-cards.index', compact(
            'institution',
            'sessions',
            'terms',
            'students',
        ));
    }

    public function show(Request $request, Student $student)
    {
        $institution = $request->user()->institution;

        abort_unless($institution instanceof Institution, 403);
        abort_unless((int) $student->institution_id === (int) $institution->id, 404);

        abort_unless($institution->hasFeature('results.report_cards'), 403);

        $validated = $request->validate([
            'academic_session_id' => ['required', 'integer'],
            'term_id' => ['required', 'integer'],
        ]);

        $data = $this->reportCards->generate(
            $institution,
            $student,
            (int) $validated['academic_session_id'],
            (int) $validated['term_id'],
        );

        return view('ict-admin.results.report-cards.show', $data);
    }
}
