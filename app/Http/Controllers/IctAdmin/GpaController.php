<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\StudentGpaSummary;
use App\Models\Term;
use App\Services\Results\GpaCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GpaController extends Controller
{
    public function calculateStudent(Request $request, GpaCalculationService $service): RedirectResponse
    {
        abort_unless(Auth::user()->hasPermission('results.calculate'), 403);

        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'academic_session_id' => ['required', 'integer'],
            'term_id' => ['required', 'integer'],
        ]);

        $institution = Auth::user()->institution;
        $student = Student::where('institution_id', $institution->id)->findOrFail($data['student_id']);
        $session = AcademicSession::where('institution_id', $institution->id)->findOrFail($data['academic_session_id']);
        $term = Term::where('institution_id', $institution->id)->findOrFail($data['term_id']);

        $summary = $service->calculateForStudent($institution, $student, $session, $term);

        return back()->with('success', "GPA calculated: {$summary->gpa} / " . $this->maximumLabel($institution));
    }

    public function calculateTerm(Request $request, GpaCalculationService $service): RedirectResponse
    {
        abort_unless(Auth::user()->hasPermission('results.calculate'), 403);

        $data = $request->validate([
            'academic_session_id' => ['required', 'integer'],
            'term_id' => ['required', 'integer'],
        ]);

        $institution = Auth::user()->institution;
        $session = AcademicSession::where('institution_id', $institution->id)->findOrFail($data['academic_session_id']);
        $term = Term::where('institution_id', $institution->id)->findOrFail($data['term_id']);

        $result = $service->calculateForTerm($institution, $session, $term);

        $message = count($result['calculated']) . ' GPA(s) calculated.';
        if ($result['failed'] !== []) {
            $message .= ' ' . count($result['failed']) . ' student(s) could not be calculated.';
        }

        return back()->with('success', $message)->with('gpa_failures', $result['failed']);
    }

    private function maximumLabel($institution): string
    {
        $settings = $institution->gradingSettings();
        return match ($settings['gpa_scale'] ?? '5') {
            '4' => '4.00',
            'custom' => number_format((float) ($settings['custom_gpa_max'] ?? 0), 2),
            default => '5.00',
        };
    }
}
