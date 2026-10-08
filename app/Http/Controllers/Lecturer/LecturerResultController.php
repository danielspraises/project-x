<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\CourseOffering;
use App\Models\CourseRegistration;
use App\Models\ResultSubmission;
use App\Models\StudentResult;
use App\Models\AssessmentComponent;
use App\Models\AssessmentScore;
use App\Models\Term;
use App\Services\Results\AssessmentSchemeService;
use App\Services\Results\AssessmentScoreService;
use App\Services\Results\ResultEntryContext;
use App\Services\Results\ResultEntryService;
use App\Services\Results\ResultSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LecturerResultController extends Controller
{
    public function __construct(
        private readonly ResultEntryContext $context,
        private readonly ResultEntryService $resultEntryService,
        private readonly ResultSubmissionService $submissionService,
        private readonly AssessmentSchemeService $schemeService,
        private readonly AssessmentScoreService $scoreService,
    ) {
    }

    /**
     * Summary: pick a session/term/course, see stats and submission status,
     * then head into either the student list or the bulk grid. No score
     * entry happens on this page itself.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $sessions = AcademicSession::where('institution_id', $user->institution_id)
            ->orderByDesc('start_date')
            ->get();

        $selectedSession = $request->integer('academic_session_id') ?: $sessions->first()?->id;

        $terms = Term::where('institution_id', $user->institution_id)
            ->where('academic_session_id', $selectedSession)
            ->orderBy('order')
            ->get();

        $selectedTerm = $request->integer('term_id') ?: $terms->first()?->id;

        $offerings = collect();
        $selectedOffering = null;
        $stats = null;
        $submission = null;
        $scheme = null;

        if ($selectedSession && $selectedTerm) {
            $offerings = $this->context->tertiaryOfferingsFor($user, $selectedSession, $selectedTerm);

            $selectedOfferingId = $request->integer('course_offering_id') ?: null;

            if ($selectedOfferingId) {
                $selectedOffering = $offerings->firstWhere('id', $selectedOfferingId);

                if ($selectedOffering) {
                    $registrationIds = $selectedOffering->registrations()->pluck('id');

                    $results = StudentResult::where('institution_id', $user->institution_id)
                        ->whereIn('course_registration_id', $registrationIds)
                        ->get();

                    $total = $registrationIds->count();
                    $entered = $results->filter(fn (StudentResult $r) => $r->total_score !== null)->count();

                    $stats = [
                        'total' => $total,
                        'entered' => $entered,
                        'missing' => $total - $entered,
                    ];

                    $submission = ResultSubmission::where('institution_id', $user->institution_id)
                        ->where('course_offering_id', $selectedOffering->id)
                        ->where('academic_session_id', $selectedSession)
                        ->where('term_id', $selectedTerm)
                        ->with('verifications')
                        ->latest()
                        ->first();

                    // Guarantees a scheme exists by the time entry happens —
                    // lazily created here rather than needing a backfill.
                    $scheme = $this->schemeService->getOrCreateForOffering($selectedOffering, $user);
                }
            }
        }

        return view('lecturer.results.index', compact(
            'sessions',
            'terms',
            'selectedSession',
            'selectedTerm',
            'offerings',
            'selectedOffering',
            'stats',
            'submission',
            'scheme'
        ));
    }

    /**
     * Assessment scheme editor: how many CAs (and quizzes/assignments/
     * attendance, if used) make up the CA share, and how much each is worth.
     * The exam share is fixed by the institution's settings and isn't
     * editable here.
     */
    public function scheme(CourseOffering $offering): View
    {
        $this->authorizeOffering($offering);

        $offering->loadMissing('course');
        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user())->load('components');

        return view('lecturer.results.scheme', compact('offering', 'scheme'));
    }

    public function schemeUpdate(Request $request, CourseOffering $offering)
    {
        $this->authorizeOffering($offering);

        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user());

        $validated = $request->validate([
            'components' => ['required', 'array', 'min:1'],
            'components.*.type' => ['required', 'in:ca,quiz,assignment,attendance'],
            'components.*.name' => ['required', 'string', 'max:100'],
            'components.*.max_score' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $components = collect($validated['components'])
            ->values()
            ->map(fn ($c, $i) => [
                'type' => $c['type'],
                'name' => $c['name'],
                'max_score' => (int) $c['max_score'],
                'order' => $i,
            ])
            ->push([
                'type' => AssessmentComponent::TYPE_EXAM,
                'name' => 'Exam',
                'max_score' => $scheme->exam_max,
                'order' => 999,
            ])
            ->all();

        try {
            $this->schemeService->replaceComponents($scheme, $components);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('lecturer.results.index', [
                'course_offering_id' => $offering->id,
                'academic_session_id' => $offering->term->academic_session_id,
                'term_id' => $offering->term_id,
            ])
            ->with('success', 'Assessment scheme updated.');
    }

    /**
     * Student list for one course offering — search, and a status badge per
     * student. Clicking a student goes to their individual entry page.
     */
    public function students(CourseOffering $offering): View
    {
        $this->authorizeOffering($offering);

        $offering->loadMissing('course');

        $rows = $offering->registrations()
            ->with('student')
            ->get()
            ->filter(fn (CourseRegistration $r) => $r->student !== null)
            ->sortBy([
                fn ($r) => $r->student->last_name,
                fn ($r) => $r->student->first_name,
            ])
            ->values();

        $existingResults = StudentResult::where('institution_id', $offering->institution_id)
            ->whereIn('course_registration_id', $rows->pluck('id'))
            ->get()
            ->keyBy('course_registration_id');

        $students = $rows->map(function (CourseRegistration $registration) use ($existingResults) {
            $result = $existingResults->get($registration->id);

            return [
                'registration_id' => $registration->id,
                'name' => $registration->student->last_name.', '.$registration->student->first_name,
                'entered' => $result !== null && $result->total_score !== null,
                'total_score' => $result?->total_score,
            ];
        })->values();

        return view('lecturer.results.students.index', compact('offering', 'students'));
    }

    /**
     * Single-student score entry.
     */
    public function studentShow(CourseOffering $offering, CourseRegistration $registration): View
    {
        $this->authorizeOffering($offering);
        $this->authorizeRegistration($offering, $registration);

        $registration->loadMissing('student');

        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user())->load('components');

        $existing = StudentResult::where('institution_id', $offering->institution_id)
            ->where('course_registration_id', $registration->id)
            ->first();

        $componentScores = $existing
            ? AssessmentScore::where('student_result_id', $existing->id)->get()->keyBy('assessment_component_id')
            : collect();

        $neighbours = $this->sortedRegistrations($offering);

        $currentIndex = $neighbours->search(fn ($r) => $r->id === $registration->id);
        $next = $currentIndex !== false ? $neighbours->get($currentIndex + 1) : null;

        return view('lecturer.results.students.show', compact('offering', 'registration', 'existing', 'next', 'scheme', 'componentScores'));
    }

    public function studentUpdate(Request $request, CourseOffering $offering, CourseRegistration $registration)
    {
        $this->authorizeOffering($offering);
        $this->authorizeRegistration($offering, $registration);

        $user = Auth::user();
        $scheme = $this->schemeService->getOrCreateForOffering($offering, $user)->load('components');

        $validated = $request->validate(array_merge([
            'academic_session_id' => ['required', 'integer'],
            'term_id' => ['required', 'integer'],
            'go_to' => ['nullable', 'in:next,list'],
            'components' => ['nullable', 'array'],
        ], $this->componentRules($scheme, 'components')));

        try {
            $this->scoreService->saveForStudent(
                $user,
                $scheme,
                ['course_registration_id' => $registration->id],
                [
                    'student_id' => $registration->student_id,
                    'academic_session_id' => $validated['academic_session_id'],
                    'term_id' => $validated['term_id'],
                    'course_registration_id' => $registration->id,
                    'course_offering_id' => $offering->id,
                ],
                $this->extractComponentScores($scheme, $validated['components'] ?? [])
            );
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', $e->validator->errors()->all()))->withInput();
        }

        if (($validated['go_to'] ?? null) === 'next') {
            $neighbours = $this->sortedRegistrations($offering);
            $currentIndex = $neighbours->search(fn ($r) => $r->id === $registration->id);
            $next = $currentIndex !== false ? $neighbours->get($currentIndex + 1) : null;

            if ($next) {
                return redirect()
                    ->route('lecturer.results.students.show', [$offering, $next])
                    ->with('success', 'Score saved.');
            }
        }

        return redirect()
            ->route('lecturer.results.students.index', $offering)
            ->with('success', 'Score saved.');
    }

    /**
     * Bulk grid — one column per assessment component.
     */
    public function bulk(Request $request, CourseOffering $offering): View
    {
        $this->authorizeOffering($offering);

        $offering->loadMissing('course');

        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user())->load('components');

        $students = $this->sortedRegistrations($offering)
            ->map(fn ($registration) => (object) [
                'registration_id' => $registration->id,
                'student' => $registration->student,
            ])
            ->values();

        $existingResults = StudentResult::where('institution_id', $offering->institution_id)
            ->where('course_offering_id', $offering->id)
            ->get()
            ->keyBy('course_registration_id');

        $componentScoresByResult = AssessmentScore::whereIn('student_result_id', $existingResults->pluck('id'))
            ->get()
            ->groupBy('student_result_id')
            ->map(fn ($rows) => $rows->keyBy('assessment_component_id'));

        $selectedSession = $request->integer('academic_session_id') ?: $offering->term?->academic_session_id;
        $selectedTerm = $request->integer('term_id') ?: $offering->term_id;

        return view('lecturer.results.bulk', compact(
            'offering',
            'students',
            'existingResults',
            'componentScoresByResult',
            'scheme',
            'selectedSession',
            'selectedTerm'
        ));
    }

    /**
     * Bulk save component scores for every student in one course offering.
     * Rows with nothing entered are skipped; each saved row goes through
     * AssessmentScoreService, which recomputes the student_results rollup
     * via the existing versioned create/update path.
     */
    public function saveScores(Request $request, CourseOffering $offering)
    {
        $this->authorizeOffering($offering);

        $user = Auth::user();
        $scheme = $this->schemeService->getOrCreateForOffering($offering, $user)->load('components');

        $validated = $request->validate(array_merge([
            'academic_session_id' => ['required', 'integer'],
            'term_id' => ['required', 'integer'],
            'scores' => ['required', 'array'],
            'scores.*.registration_id' => ['required', 'integer'],
            'scores.*.student_id' => ['required', 'integer'],
        ], $this->componentRules($scheme, 'scores.*.components')));

        $validRegistrationIds = $offering->registrations()->pluck('id')->all();
        $errors = [];

        foreach ($validated['scores'] as $row) {
            if (! in_array((int) $row['registration_id'], $validRegistrationIds, true)) {
                continue;
            }

            $componentScores = $this->extractComponentScores($scheme, $row['components'] ?? []);

            $hasAny = collect($componentScores)->contains(fn ($c) => $c['score'] !== null || $c['is_absent']);

            if (! $hasAny) {
                continue; // untouched row
            }

            try {
                $this->scoreService->saveForStudent(
                    $user,
                    $scheme,
                    ['course_registration_id' => $row['registration_id']],
                    [
                        'student_id' => $row['student_id'],
                        'academic_session_id' => $validated['academic_session_id'],
                        'term_id' => $validated['term_id'],
                        'course_registration_id' => $row['registration_id'],
                        'course_offering_id' => $offering->id,
                    ],
                    $componentScores
                );
            } catch (ValidationException $e) {
                $errors[] = "Student #{$row['student_id']}: ".implode(' ', $e->validator->errors()->all());
            }
        }

        if ($errors !== []) {
            return back()->with('error', implode(' | ', $errors));
        }

        return redirect()
            ->route('lecturer.results.index', [
                'academic_session_id' => $validated['academic_session_id'],
                'term_id' => $validated['term_id'],
                'course_offering_id' => $offering->id,
            ])
            ->with('success', 'Scores saved.');
    }

    /**
     * Per-component validation rules: each score must be numeric and
     * between 0 and that component's own max.
     */
    private function componentRules($scheme, string $prefix): array
    {
        $rules = [];

        foreach ($scheme->components as $component) {
            $rules["{$prefix}.{$component->id}.score"] = ['nullable', 'numeric', 'min:0', 'max:'.$component->max_score];
            $rules["{$prefix}.{$component->id}.is_absent"] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    /**
     * @return array<int, array{score: float|null, is_absent: bool}>
     */
    private function extractComponentScores($scheme, array $input): array
    {
        $out = [];

        foreach ($scheme->components as $component) {
            $row = $input[$component->id] ?? [];
            $isAbsent = (bool) ($row['is_absent'] ?? false);
            $score = $isAbsent ? null : ($row['score'] ?? null);

            $out[$component->id] = [
                'score' => ($score === '' || $score === null) ? null : (float) $score,
                'is_absent' => $isAbsent,
            ];
        }

        return $out;
    }

    private function sortedRegistrations(CourseOffering $offering)
    {
        return $offering->registrations()
            ->with('student')
            ->get()
            ->filter(fn (CourseRegistration $r) => $r->student !== null)
            ->sortBy([
                fn ($r) => $r->student->last_name,
                fn ($r) => $r->student->first_name,
            ])
            ->values();
    }

    public function submit(Request $request, CourseOffering $offering)
    {
        $this->authorizeOffering($offering);

        $user = Auth::user();

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->submissionService->submitTertiary($user, $offering, $validated['note'] ?? null);
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', $e->validator->errors()->all()));
        }

        return back()->with('success', 'Results submitted for department review.');
    }

    private function authorizeOffering(CourseOffering $offering): void
    {
        $user = Auth::user();

        abort_if((int) $offering->institution_id !== (int) $user->institution_id, 404);

        abort_unless(
            $user->hasPermission('results.view') || (int) $offering->lecturer_id === (int) $user->id,
            403,
            'You are not the assigned lecturer for this course offering.'
        );
    }

    private function authorizeRegistration(CourseOffering $offering, CourseRegistration $registration): void
    {
        abort_if((int) $registration->course_offering_id !== (int) $offering->id, 404);
    }

    /**
     * CSV import form — shows the download-template link, upload form, and
     * the result of the last import (via session flash) if there was one.
     */
    public function importForm(Request $request, CourseOffering $offering): View
    {
        $this->authorizeOffering($offering);

        $offering->loadMissing('course', 'term');

        $selectedSession = $request->integer('academic_session_id') ?: $offering->term?->academic_session_id;
        $selectedTerm = $request->integer('term_id') ?: $offering->term_id;

        return view('lecturer.results.import', compact('offering', 'selectedSession', 'selectedTerm'));
    }

    /**
     * Downloadable CSV template, pre-filled with each student's matric number
     * and current scores so re-downloading shows exactly what's still missing.
     */
    public function importTemplate(CourseOffering $offering)
    {
        $this->authorizeOffering($offering);

        $offering->loadMissing('course');

        $registrations = $offering->registrations()
            ->with('student')
            ->get()
            ->filter(fn (CourseRegistration $r) => $r->student !== null)
            ->sortBy([
                fn ($r) => $r->student->last_name,
                fn ($r) => $r->student->first_name,
            ])
            ->values();

        $existingResults = StudentResult::where('institution_id', $offering->institution_id)
            ->whereIn('course_registration_id', $registrations->pluck('id'))
            ->get()
            ->keyBy('course_registration_id');

        $filename = 'scores-'.($offering->course->code ?? $offering->id).'-template.csv';

        return response()->streamDownload(function () use ($registrations, $existingResults) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['matric_number', 'student_name', 'ca_score', 'exam_score']);

            foreach ($registrations as $registration) {
                $existing = $existingResults->get($registration->id);

                fputcsv($out, [
                    $registration->student->matric_number,
                    $registration->student->last_name.', '.$registration->student->first_name,
                    $existing?->ca_score,
                    $existing?->exam_score,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Parse and validate an uploaded CSV, but write nothing yet. Staged rows
     * are held in the session, keyed per offering, until the lecturer
     * confirms them on the preview page (importApply) or discards them
     * (importDiscard).
     */
    public function importUpload(Request $request, CourseOffering $offering)
    {
        $this->authorizeOffering($offering);

        $user = Auth::user();

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'academic_session_id' => ['required', 'integer'],
            'term_id' => ['required', 'integer'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);
            return back()->with('error', 'The uploaded file is empty.');
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);

        $columnIndex = [];
        foreach (['matric_number', 'ca_score', 'exam_score'] as $column) {
            $pos = array_search($column, $header, true);
            $columnIndex[$column] = $pos === false ? null : $pos;
        }

        if ($columnIndex['matric_number'] === null) {
            fclose($handle);
            return back()->with('error', 'The CSV is missing a "matric_number" column. Please use the downloaded template and don\'t rename its headers.');
        }

        // Registrations for this offering, keyed by the student's matric
        // number, so each CSV row can be resolved without trusting anything
        // in the file beyond the matric number itself.
        $registrationsByMatric = $offering->registrations()
            ->with('student')
            ->get()
            ->filter(fn (CourseRegistration $r) => $r->student !== null && $r->student->matric_number !== null)
            ->keyBy(fn (CourseRegistration $r) => strtoupper(trim($r->student->matric_number)));

        $existingResults = StudentResult::where('institution_id', $user->institution_id)
            ->whereIn('course_registration_id', $registrationsByMatric->pluck('id'))
            ->get()
            ->keyBy('course_registration_id');

        $stagedRows = [];
        $rowNum = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // fully blank line
            }

            $matric = strtoupper(trim((string) ($data[$columnIndex['matric_number']] ?? '')));

            if ($matric === '') {
                $stagedRows[] = ['row' => $rowNum, 'error' => 'Blank matric number.'];
                continue;
            }

            $registration = $registrationsByMatric->get($matric);

            if (! $registration) {
                $stagedRows[] = ['row' => $rowNum, 'matric_number' => $matric, 'error' => 'Matric number not recognized for this course.'];
                continue;
            }

            $caRaw = $columnIndex['ca_score'] !== null ? trim((string) ($data[$columnIndex['ca_score']] ?? '')) : '';
            $examRaw = $columnIndex['exam_score'] !== null ? trim((string) ($data[$columnIndex['exam_score']] ?? '')) : '';

            if ($caRaw === '' && $examRaw === '') {
                continue; // nothing on this row to stage
            }

            if ($caRaw !== '' && ! is_numeric($caRaw)) {
                $stagedRows[] = ['row' => $rowNum, 'matric_number' => $matric, 'student_name' => $registration->student->last_name.', '.$registration->student->first_name, 'error' => "CA score \"{$caRaw}\" is not numeric."];
                continue;
            }

            if ($examRaw !== '' && ! is_numeric($examRaw)) {
                $stagedRows[] = ['row' => $rowNum, 'matric_number' => $matric, 'student_name' => $registration->student->last_name.', '.$registration->student->first_name, 'error' => "Exam score \"{$examRaw}\" is not numeric."];
                continue;
            }

            $existing = $existingResults->get($registration->id);

            // Blank cell = keep the existing value, never overwrite with null.
            $newCa = $caRaw !== '' ? (float) $caRaw : $existing?->ca_score;
            $newExam = $examRaw !== '' ? (float) $examRaw : $existing?->exam_score;

            $stagedRows[] = [
                'row' => $rowNum,
                'registration_id' => $registration->id,
                'student_id' => $registration->student_id,
                'matric_number' => $matric,
                'student_name' => $registration->student->last_name.', '.$registration->student->first_name,
                'current_ca' => $existing?->ca_score,
                'current_exam' => $existing?->exam_score,
                'new_ca' => $newCa,
                'new_exam' => $newExam,
                'error' => null,
            ];
        }

        fclose($handle);

        if ($stagedRows === []) {
            return back()->with('error', 'No usable rows found in that file.');
        }

        session()->put("lecturer_import_staged.{$offering->id}", [
            'academic_session_id' => $request->integer('academic_session_id'),
            'term_id' => $request->integer('term_id'),
            'rows' => $stagedRows,
        ]);

        return redirect()->route('lecturer.results.import.preview', $offering);
    }

    /**
     * Show the staged rows for review before anything is written.
     */
    public function importPreview(CourseOffering $offering): View|\Illuminate\Http\RedirectResponse
    {
        $this->authorizeOffering($offering);

        $staged = session("lecturer_import_staged.{$offering->id}");

        if (! $staged) {
            return redirect()
                ->route('lecturer.results.import', $offering)
                ->with('error', 'Nothing to preview — upload a file first.');
        }

        $offering->loadMissing('course');

        return view('lecturer.results.import-preview', [
            'offering' => $offering,
            'rows' => collect($staged['rows']),
            'selectedSession' => $staged['academic_session_id'],
            'selectedTerm' => $staged['term_id'],
        ]);
    }

    /**
     * Commit the staged rows that parsed cleanly. Rows with an error are
     * skipped and reported, same as before — they were just caught earlier
     * (at upload time) instead of silently mixed into the write pass.
     */
    public function importApply(Request $request, CourseOffering $offering)
    {
        $this->authorizeOffering($offering);

        $user = Auth::user();
        $staged = session("lecturer_import_staged.{$offering->id}");

        if (! $staged) {
            return redirect()
                ->route('lecturer.results.import', $offering)
                ->with('error', 'Nothing staged to apply — upload a file first.');
        }

        $updated = 0;
        $skipped = 0;
        $rowErrors = [];

        foreach ($staged['rows'] as $row) {
            if (! empty($row['error'])) {
                $label = $row['student_name'] ?? ($row['matric_number'] ?? '');
                $rowErrors[] = "Row {$row['row']}".($label ? " ({$label})" : '').": {$row['error']}";
                $skipped++;
                continue;
            }

            $existing = StudentResult::where('institution_id', $user->institution_id)
                ->where('course_registration_id', $row['registration_id'])
                ->first();

            $payload = [
                'student_id' => $row['student_id'],
                'academic_session_id' => $staged['academic_session_id'],
                'term_id' => $staged['term_id'],
                'course_registration_id' => $row['registration_id'],
                'course_offering_id' => $offering->id,
                'ca_score' => $row['new_ca'],
                'exam_score' => $row['new_exam'],
            ];

            try {
                if ($existing) {
                    $this->resultEntryService->update($user, $existing, $payload);
                } else {
                    $this->resultEntryService->create($user, $payload);
                }
                $updated++;
            } catch (ValidationException $e) {
                $rowErrors[] = "Row {$row['row']} ({$row['student_name']}): ".implode(' ', $e->validator->errors()->all());
                $skipped++;
            }
        }

        session()->forget("lecturer_import_staged.{$offering->id}");

        return redirect()
            ->route('lecturer.results.import', [
                $offering,
                'academic_session_id' => $staged['academic_session_id'],
                'term_id' => $staged['term_id'],
            ])
            ->with('import_summary', [
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $rowErrors,
            ]);
    }

    /**
     * Discard the staged rows without writing anything.
     */
    public function importDiscard(CourseOffering $offering)
    {
        $this->authorizeOffering($offering);

        session()->forget("lecturer_import_staged.{$offering->id}");

        return redirect()
            ->route('lecturer.results.import', $offering)
            ->with('error', 'Import discarded — nothing was saved.');
    }
}
