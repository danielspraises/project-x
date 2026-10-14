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
use App\Services\Results\AssessmentCsvHelper;
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
        private readonly AssessmentCsvHelper $csv,
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
        $schemeNeedsAttention = false;

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
                    $schemeNeedsAttention = ! $this->schemeService->isConsistent($scheme);
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
            'scheme',
            'schemeNeedsAttention'
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

        if ((int) $scheme->ca_max === 0) {
            return back()->with('error', 'This institution awards no marks to continuous assessment, so there is no CA split to configure.');
        }

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
            ->when($scheme->exam_max > 0, fn ($c) => $c->push([
                'type' => AssessmentComponent::TYPE_EXAM,
                'name' => 'Exam',
                'max_score' => $scheme->exam_max,
                'order' => 999,
            ]))
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
    public function studentShow(CourseOffering $offering, CourseRegistration $registration): View|\Illuminate\Http\RedirectResponse
    {
        $this->authorizeOffering($offering);
        $this->authorizeRegistration($offering, $registration);

        $registration->loadMissing('student');

        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user())->load('components');

        if (! $this->schemeService->isConsistent($scheme)) {
            return redirect()
                ->route('lecturer.results.scheme', $offering)
                ->with('error', "The institution's CA/Exam weights changed, so this assessment scheme needs updating before scores can be entered.");
        }

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
    public function bulk(Request $request, CourseOffering $offering): View|\Illuminate\Http\RedirectResponse
    {
        $this->authorizeOffering($offering);

        $offering->loadMissing('course');

        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user())->load('components');

        if (! $this->schemeService->isConsistent($scheme)) {
            return redirect()
                ->route('lecturer.results.scheme', $offering)
                ->with('error', "The institution's CA/Exam weights changed, so this assessment scheme needs updating before scores can be entered.");
        }

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
     * CSV import form.
     */
    public function importForm(Request $request, CourseOffering $offering): View|\Illuminate\Http\RedirectResponse
    {
        $this->authorizeOffering($offering);

        $offering->loadMissing('course', 'term');

        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user())->load('components');

        if (! $this->schemeService->isConsistent($scheme)) {
            return $this->schemeNeedsUpdateRedirect($offering);
        }

        $selectedSession = $request->integer('academic_session_id') ?: $offering->term?->academic_session_id;
        $selectedTerm = $request->integer('term_id') ?: $offering->term_id;

        return view('lecturer.results.import', compact('offering', 'selectedSession', 'selectedTerm', 'scheme'));
    }

    /**
     * Downloadable CSV template: one column per assessment component,
     * pre-filled with the saved score ("A" for absent) so re-downloading
     * shows exactly what's still missing.
     */
    public function importTemplate(CourseOffering $offering)
    {
        $this->authorizeOffering($offering);

        $offering->loadMissing('course');

        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user())->load('components');

        if (! $this->schemeService->isConsistent($scheme)) {
            return $this->schemeNeedsUpdateRedirect($offering);
        }

        $registrations = $this->sortedRegistrations($offering);

        $results = StudentResult::where('institution_id', $offering->institution_id)
            ->whereIn('course_registration_id', $registrations->pluck('id'))
            ->get()
            ->keyBy('course_registration_id');

        $scoresByResult = AssessmentScore::whereIn('student_result_id', $results->pluck('id'))
            ->get()
            ->groupBy('student_result_id')
            ->map(fn ($rows) => $rows->keyBy('assessment_component_id'));

        $components = $scheme->components;
        $filename = 'scores-'.($offering->course->code ?? $offering->id).'-template.csv';

        return response()->streamDownload(function () use ($registrations, $results, $scoresByResult, $components) {
            $out = fopen('php://output', 'w');

            fputcsv($out, array_merge(
                ['matric_number', 'student_name'],
                $components->map(fn ($c) => $this->csv->headerFor($c))->all()
            ));

            foreach ($registrations as $registration) {
                $result = $results->get($registration->id);
                $rowScores = $result ? ($scoresByResult->get($result->id) ?? collect()) : collect();

                fputcsv($out, array_merge(
                    [
                        $registration->student->matric_number,
                        $registration->student->last_name.', '.$registration->student->first_name,
                    ],
                    $components->map(fn ($c) => $this->csv->describe($rowScores->get($c->id)))->all()
                ));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Parse and validate an uploaded CSV, writing nothing yet — staged in
     * the session until confirmed on the preview page (importApply) or
     * discarded (importDiscard). A blank cell leaves that score as it is, an
     * "A" marks the student absent, and a score above its component's max
     * rejects the whole row with a message naming the component.
     */
    public function importUpload(Request $request, CourseOffering $offering)
    {
        $this->authorizeOffering($offering);

        $user = Auth::user();

        $scheme = $this->schemeService->getOrCreateForOffering($offering, $user)->load('components');

        if (! $this->schemeService->isConsistent($scheme)) {
            return $this->schemeNeedsUpdateRedirect($offering);
        }

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

        // Excel's "CSV UTF-8" prepends a byte-order mark to the first heading.
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);

        $keyIndex = array_search('matric_number', array_map(fn ($h) => strtolower(trim((string) $h)), $header), true);

        if ($keyIndex === false) {
            fclose($handle);
            return back()->with('error', 'The CSV is missing a "matric_number" column. Please use the downloaded template and don\'t rename its headings.');
        }

        [$columnMap, $unknownHeadings] = $this->csv->mapComponentColumns($header, $scheme->components);

        if ($unknownHeadings !== []) {
            fclose($handle);
            return back()->with('error', 'This file was made for an older version of the assessment scheme ("'.implode('", "', $unknownHeadings).'"). Download a fresh template and try again.');
        }

        if ($columnMap === []) {
            fclose($handle);
            return back()->with('error', 'No score columns found. Use the downloaded template and keep its column headings.');
        }

        $registrationsByMatric = $this->sortedRegistrations($offering)
            ->filter(fn (CourseRegistration $r) => $r->student->matric_number !== null)
            ->keyBy(fn (CourseRegistration $r) => strtoupper(trim($r->student->matric_number)));

        $results = StudentResult::where('institution_id', $user->institution_id)
            ->whereIn('course_registration_id', $registrationsByMatric->pluck('id'))
            ->get()
            ->keyBy('course_registration_id');

        $scoresByResult = AssessmentScore::whereIn('student_result_id', $results->pluck('id'))
            ->get()
            ->groupBy('student_result_id')
            ->map(fn ($rows) => $rows->keyBy('assessment_component_id'));

        $stagedRows = [];
        $rowNum = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // fully blank line
            }

            $matric = strtoupper(trim((string) ($data[$keyIndex] ?? '')));

            if ($matric === '') {
                $stagedRows[] = ['row' => $rowNum, 'error' => 'Blank matric number.'];
                continue;
            }

            $registration = $registrationsByMatric->get($matric);

            if (! $registration) {
                $stagedRows[] = ['row' => $rowNum, 'matric_number' => $matric, 'error' => 'Matric number not recognized for this course.'];
                continue;
            }

            $name = $registration->student->last_name.', '.$registration->student->first_name;
            $result = $results->get($registration->id);
            $rowScores = $result ? ($scoresByResult->get($result->id) ?? collect()) : collect();

            $cells = [];
            $cellErrors = [];

            foreach ($columnMap as $index => $component) {
                $parsed = $this->csv->parseCell((string) ($data[$index] ?? ''), $component);

                if ($parsed['error'] !== null) {
                    $cellErrors[] = "{$component->name}: {$parsed['error']}";
                    continue;
                }

                if ($parsed['blank']) {
                    continue;
                }

                $cells[$component->id] = [
                    'current' => $this->csv->describe($rowScores->get($component->id)),
                    'new' => $parsed['absent'] ? 'A' : $this->csv->formatNumber($parsed['value']),
                    'score' => $parsed['value'],
                    'is_absent' => $parsed['absent'],
                ];
            }

            if ($cellErrors !== []) {
                $stagedRows[] = ['row' => $rowNum, 'matric_number' => $matric, 'student_name' => $name, 'error' => implode('; ', $cellErrors)];
                continue;
            }

            if ($cells === []) {
                continue; // nothing on this row to stage
            }

            $stagedRows[] = [
                'row' => $rowNum,
                'registration_id' => $registration->id,
                'student_id' => $registration->student_id,
                'matric_number' => $matric,
                'student_name' => $name,
                'cells' => $cells,
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

        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user())->load('components');

        return view('lecturer.results.import-preview', [
            'offering' => $offering,
            'scheme' => $scheme,
            'rows' => collect($staged['rows']),
            'selectedSession' => $staged['academic_session_id'],
            'selectedTerm' => $staged['term_id'],
        ]);
    }

    /**
     * Commit the staged rows that parsed cleanly. Only the components that
     * had a value in the file are saved; everything else on that student is
     * left as it was, and each save recomputes the student's total through
     * AssessmentScoreService.
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

        $scheme = $this->schemeService->getOrCreateForOffering($offering, $user)->load('components');

        if (! $this->schemeService->isConsistent($scheme)) {
            return $this->schemeNeedsUpdateRedirect($offering);
        }

        $validComponentIds = $scheme->components->pluck('id')->map(fn ($id) => (int) $id)->all();

        $updated = 0;
        $skipped = 0;
        $rowErrors = [];

        foreach ($staged['rows'] as $row) {
            $label = $row['student_name'] ?? ($row['matric_number'] ?? '');
            $prefix = "Row {$row['row']}".($label ? " ({$label})" : '');

            if (! empty($row['error'])) {
                $rowErrors[] = "{$prefix}: {$row['error']}";
                $skipped++;
                continue;
            }

            $componentScores = [];
            $stale = false;

            foreach ($row['cells'] as $componentId => $cell) {
                if (! in_array((int) $componentId, $validComponentIds, true)) {
                    $stale = true;
                    break;
                }

                $componentScores[(int) $componentId] = [
                    'score' => $cell['score'],
                    'is_absent' => (bool) $cell['is_absent'],
                ];
            }

            if ($stale) {
                $rowErrors[] = "{$prefix}: the assessment scheme changed after this file was uploaded — upload it again.";
                $skipped++;
                continue;
            }

            try {
                $this->scoreService->saveForStudent(
                    $user,
                    $scheme,
                    ['course_registration_id' => $row['registration_id']],
                    [
                        'student_id' => $row['student_id'],
                        'academic_session_id' => $staged['academic_session_id'],
                        'term_id' => $staged['term_id'],
                        'course_registration_id' => $row['registration_id'],
                        'course_offering_id' => $offering->id,
                    ],
                    $componentScores
                );
                $updated++;
            } catch (ValidationException $e) {
                $rowErrors[] = "{$prefix}: ".implode(' ', $e->validator->errors()->all());
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

    private function schemeNeedsUpdateRedirect(CourseOffering $offering)
    {
        return redirect()
            ->route('lecturer.results.scheme', $offering)
            ->with('error', "The institution's CA/Exam weights changed, so this assessment scheme needs updating before scores can be imported.");
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
