<?php

namespace App\Http\Controllers\SubjectTeacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ResultSubmission;
use App\Models\Student;
use App\Models\StudentResult;
use App\Models\SubjectOffering;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubjectTeacherResultController extends Controller
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
     * Summary: pick a session/term/subject offering, see stats and
     * submission status, then head into the student list or bulk grid.
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
            $offerings = $this->context->basicFor($user, $selectedSession, $selectedTerm);

            $selectedOfferingId = $request->integer('subject_offering_id') ?: null;

            if ($selectedOfferingId) {
                $selectedOffering = $offerings->firstWhere('id', $selectedOfferingId);

                if ($selectedOffering) {
                    $studentIds = DB::table('subject_registrations')
                        ->where('institution_id', $user->institution_id)
                        ->where('subject_offering_id', $selectedOffering->id)
                        ->pluck('student_id');

                    $results = StudentResult::where('institution_id', $user->institution_id)
                        ->where('subject_offering_id', $selectedOffering->id)
                        ->whereIn('student_id', $studentIds)
                        ->get();

                    $total = $studentIds->count();
                    $entered = $results->filter(fn (StudentResult $r) => $r->total_score !== null)->count();

                    $stats = [
                        'total' => $total,
                        'entered' => $entered,
                        'missing' => $total - $entered,
                    ];

                    $submission = ResultSubmission::where('institution_id', $user->institution_id)
                        ->where('subject_offering_id', $selectedOffering->id)
                        ->where('academic_session_id', $selectedSession)
                        ->where('term_id', $selectedTerm)
                        ->with('verifications')
                        ->latest()
                        ->first();

                    $scheme = $this->schemeService->getOrCreateForOffering($selectedOffering, $user);
                    $schemeNeedsAttention = ! $this->schemeService->isConsistent($scheme);
                }
            }
        }

        return view('subject-teacher.results.index', compact(
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
     * Assessment scheme editor — same shape as the Lecturer one, but for a
     * subject offering. The exam share is fixed by institution settings.
     */
    public function scheme(SubjectOffering $offering): View
    {
        $this->authorizeOffering($offering);

        $offering->loadMissing('subject');
        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user())->load('components');

        return view('subject-teacher.results.scheme', compact('offering', 'scheme'));
    }

    public function schemeUpdate(Request $request, SubjectOffering $offering)
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
            ->route('subject-teacher.results.index', [
                'subject_offering_id' => $offering->id,
                'academic_session_id' => $offering->academic_session_id,
                'term_id' => $offering->term_id,
            ])
            ->with('success', 'Assessment scheme updated.');
    }

    /**
     * Student list for one subject offering — search, status badge per
     * student, click through to their individual entry page.
     */
    public function students(SubjectOffering $offering): View
    {
        $this->authorizeOffering($offering);

        $offering->loadMissing('subject', 'schoolClass', 'arm');

        $rows = $this->context->studentsForBasic(Auth::user(), $offering)
            ->sortBy([fn ($s) => $s->last_name, fn ($s) => $s->first_name])
            ->values();

        $existingResults = StudentResult::where('institution_id', $offering->institution_id)
            ->where('subject_offering_id', $offering->id)
            ->whereIn('student_id', $rows->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $students = $rows->map(function ($student) use ($existingResults) {
            $result = $existingResults->get($student->id);

            return [
                'student_id' => $student->id,
                'name' => $student->last_name.', '.$student->first_name,
                'entered' => $result !== null && $result->total_score !== null,
                'total_score' => $result?->total_score,
            ];
        })->values();

        return view('subject-teacher.results.students.index', compact('offering', 'students'));
    }

    public function studentShow(SubjectOffering $offering, Student $student): View|\Illuminate\Http\RedirectResponse
    {
        $this->authorizeOffering($offering);
        $this->authorizeStudent($offering, $student);

        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user())->load('components');

        if (! $this->schemeService->isConsistent($scheme)) {
            return redirect()
                ->route('subject-teacher.results.scheme', $offering)
                ->with('error', "The institution's CA/Exam weights changed, so this assessment scheme needs updating before scores can be entered.");
        }

        $existing = StudentResult::where('institution_id', $offering->institution_id)
            ->where('subject_offering_id', $offering->id)
            ->where('student_id', $student->id)
            ->first();

        $componentScores = $existing
            ? AssessmentScore::where('student_result_id', $existing->id)->get()->keyBy('assessment_component_id')
            : collect();

        $neighbours = $this->sortedStudents($offering);

        $currentIndex = $neighbours->search(fn ($s) => $s->id === $student->id);
        $next = $currentIndex !== false ? $neighbours->get($currentIndex + 1) : null;

        return view('subject-teacher.results.students.show', compact('offering', 'student', 'existing', 'next', 'scheme', 'componentScores'));
    }

    public function studentUpdate(Request $request, SubjectOffering $offering, Student $student)
    {
        $this->authorizeOffering($offering);
        $this->authorizeStudent($offering, $student);

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
                ['subject_offering_id' => $offering->id, 'student_id' => $student->id],
                [
                    'student_id' => $student->id,
                    'academic_session_id' => $validated['academic_session_id'],
                    'term_id' => $validated['term_id'],
                    'subject_offering_id' => $offering->id,
                    'class_id' => $offering->class_id,
                    // Each student has their own arm even on a class-wide (arm_id
                    // null) offering, so this comes from the student, not the offering.
                    'arm_id' => $student->arm_id,
                ],
                $this->extractComponentScores($scheme, $validated['components'] ?? [])
            );
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', $e->validator->errors()->all()))->withInput();
        }

        if (($validated['go_to'] ?? null) === 'next') {
            $neighbours = $this->sortedStudents($offering);
            $currentIndex = $neighbours->search(fn ($s) => $s->id === $student->id);
            $next = $currentIndex !== false ? $neighbours->get($currentIndex + 1) : null;

            if ($next) {
                return redirect()
                    ->route('subject-teacher.results.students.show', [$offering, $next->id])
                    ->with('success', 'Score saved.');
            }
        }

        return redirect()
            ->route('subject-teacher.results.students.index', $offering)
            ->with('success', 'Score saved.');
    }

    public function bulk(Request $request, SubjectOffering $offering): View|\Illuminate\Http\RedirectResponse
    {
        $this->authorizeOffering($offering);

        $offering->loadMissing('subject', 'schoolClass', 'arm');

        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user())->load('components');

        if (! $this->schemeService->isConsistent($scheme)) {
            return redirect()
                ->route('subject-teacher.results.scheme', $offering)
                ->with('error', "The institution's CA/Exam weights changed, so this assessment scheme needs updating before scores can be entered.");
        }

        $students = $this->sortedStudents($offering);

        $existingResults = StudentResult::where('institution_id', $offering->institution_id)
            ->where('subject_offering_id', $offering->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $componentScoresByResult = AssessmentScore::whereIn('student_result_id', $existingResults->pluck('id'))
            ->get()
            ->groupBy('student_result_id')
            ->map(fn ($rows) => $rows->keyBy('assessment_component_id'));

        $selectedSession = $request->integer('academic_session_id') ?: $offering->academic_session_id;
        $selectedTerm = $request->integer('term_id') ?: $offering->term_id;

        return view('subject-teacher.results.bulk', compact(
            'offering',
            'students',
            'existingResults',
            'componentScoresByResult',
            'scheme',
            'selectedSession',
            'selectedTerm'
        ));
    }

    public function saveScores(Request $request, SubjectOffering $offering)
    {
        $this->authorizeOffering($offering);

        $user = Auth::user();
        $scheme = $this->schemeService->getOrCreateForOffering($offering, $user)->load('components');

        $validated = $request->validate(array_merge([
            'academic_session_id' => ['required', 'integer'],
            'term_id' => ['required', 'integer'],
            'scores' => ['required', 'array'],
            'scores.*.student_id' => ['required', 'integer'],
        ], $this->componentRules($scheme, 'scores.*.components')));

        $registeredStudentIds = $this->sortedStudents($offering)->pluck('id')->all();

        $studentArms = Student::whereIn('id', collect($validated['scores'])->pluck('student_id'))
            ->pluck('arm_id', 'id');

        $errors = [];

        foreach ($validated['scores'] as $row) {
            if (! in_array((int) $row['student_id'], $registeredStudentIds, true)) {
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
                    ['subject_offering_id' => $offering->id, 'student_id' => $row['student_id']],
                    [
                        'student_id' => $row['student_id'],
                        'academic_session_id' => $validated['academic_session_id'],
                        'term_id' => $validated['term_id'],
                        'subject_offering_id' => $offering->id,
                        'class_id' => $offering->class_id,
                        'arm_id' => $studentArms->get($row['student_id']),
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
            ->route('subject-teacher.results.index', [
                'academic_session_id' => $validated['academic_session_id'],
                'term_id' => $validated['term_id'],
                'subject_offering_id' => $offering->id,
            ])
            ->with('success', 'Scores saved.');
    }

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

    private function sortedStudents(SubjectOffering $offering)
    {
        return $this->context->studentsForBasic(Auth::user(), $offering)
            ->sortBy([fn ($s) => $s->last_name, fn ($s) => $s->first_name])
            ->values();
    }

    public function submit(Request $request, SubjectOffering $offering)
    {
        $this->authorizeOffering($offering);

        $user = Auth::user();

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->submissionService->submitBasic($user, $offering, $validated['note'] ?? null);
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', $e->validator->errors()->all()));
        }

        return back()->with('success', 'Results submitted for class teacher review.');
    }

    private function authorizeOffering(SubjectOffering $offering): void
    {
        $user = Auth::user();

        abort_if((int) $offering->institution_id !== (int) $user->institution_id, 404);

        abort_unless(
            $user->hasPermission('results.view') || $this->context->canTeachBasicOffering($user, $offering),
            403,
            'You are not assigned to teach this subject offering.'
        );
    }

    private function authorizeStudent(SubjectOffering $offering, Student $student): void
    {
        $exists = DB::table('subject_registrations')
            ->where('institution_id', $offering->institution_id)
            ->where('subject_offering_id', $offering->id)
            ->where('student_id', $student->id)
            ->exists();

        abort_unless($exists, 404);
    }

    /**
     * CSV import form.
     */
    public function importForm(Request $request, SubjectOffering $offering): View|\Illuminate\Http\RedirectResponse
    {
        $this->authorizeOffering($offering);

        $offering->loadMissing('subject', 'schoolClass', 'arm');

        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user())->load('components');

        if (! $this->schemeService->isConsistent($scheme)) {
            return $this->schemeNeedsUpdateRedirect($offering);
        }

        $selectedSession = $request->integer('academic_session_id') ?: $offering->academic_session_id;
        $selectedTerm = $request->integer('term_id') ?: $offering->term_id;

        return view('subject-teacher.results.import', compact('offering', 'selectedSession', 'selectedTerm', 'scheme'));
    }

    /**
     * Downloadable CSV template: one column per assessment component,
     * pre-filled with the saved score ("A" for absent).
     */
    public function importTemplate(SubjectOffering $offering)
    {
        $this->authorizeOffering($offering);

        $offering->loadMissing('subject');

        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user())->load('components');

        if (! $this->schemeService->isConsistent($scheme)) {
            return $this->schemeNeedsUpdateRedirect($offering);
        }

        $students = $this->sortedStudents($offering);

        $results = StudentResult::where('institution_id', $offering->institution_id)
            ->where('subject_offering_id', $offering->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $scoresByResult = AssessmentScore::whereIn('student_result_id', $results->pluck('id'))
            ->get()
            ->groupBy('student_result_id')
            ->map(fn ($rows) => $rows->keyBy('assessment_component_id'));

        $components = $scheme->components;
        $filename = 'scores-'.($offering->subject->name ?? $offering->id).'-template.csv';

        return response()->streamDownload(function () use ($students, $results, $scoresByResult, $components) {
            $out = fopen('php://output', 'w');

            fputcsv($out, array_merge(
                ['admission_number', 'student_name'],
                $components->map(fn ($c) => $this->csv->headerFor($c))->all()
            ));

            foreach ($students as $student) {
                $result = $results->get($student->id);
                $rowScores = $result ? ($scoresByResult->get($result->id) ?? collect()) : collect();

                fputcsv($out, array_merge(
                    [$student->admission_number, $student->last_name.', '.$student->first_name],
                    $components->map(fn ($c) => $this->csv->describe($rowScores->get($c->id)))->all()
                ));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Parse and validate an uploaded CSV, writing nothing yet — staged in
     * the session until confirmed (importApply) or discarded
     * (importDiscard). Blank = leave as is, "A" = absent, and a score over
     * its component's max rejects that row.
     */
    public function importUpload(Request $request, SubjectOffering $offering)
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

        $keyIndex = array_search('admission_number', array_map(fn ($h) => strtolower(trim((string) $h)), $header), true);

        if ($keyIndex === false) {
            fclose($handle);
            return back()->with('error', 'The CSV is missing an "admission_number" column. Please use the downloaded template and don\'t rename its headings.');
        }

        [$columnMap, $unknownHeadings] = $this->csv->mapComponentColumns($header, $scheme->components);

        if ($unknownHeadings !== []) {
            fclose($handle);
            return back()->with('error', 'These columns don\'t match the current assessment scheme ("'.implode('", "', $unknownHeadings).'"). Download a fresh template and try again.');
        }

        if ($columnMap === []) {
            fclose($handle);
            return back()->with('error', 'No score columns found. Use the downloaded template and keep its column headings.');
        }

        $studentsByAdmission = $this->sortedStudents($offering)
            ->filter(fn ($s) => $s->admission_number !== null)
            ->keyBy(fn ($s) => strtoupper(trim($s->admission_number)));

        $results = StudentResult::where('institution_id', $user->institution_id)
            ->where('subject_offering_id', $offering->id)
            ->whereIn('student_id', $studentsByAdmission->pluck('id'))
            ->get()
            ->keyBy('student_id');

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

            $admission = strtoupper(trim((string) ($data[$keyIndex] ?? '')));

            if ($admission === '') {
                $stagedRows[] = ['row' => $rowNum, 'error' => 'Blank admission number.'];
                continue;
            }

            $student = $studentsByAdmission->get($admission);

            if (! $student) {
                $stagedRows[] = ['row' => $rowNum, 'admission_number' => $admission, 'error' => 'Admission number not recognized for this subject.'];
                continue;
            }

            $name = $student->last_name.', '.$student->first_name;
            $result = $results->get($student->id);
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
                $stagedRows[] = ['row' => $rowNum, 'admission_number' => $admission, 'student_name' => $name, 'error' => implode('; ', $cellErrors)];
                continue;
            }

            if ($cells === []) {
                continue; // nothing on this row to stage
            }

            $stagedRows[] = [
                'row' => $rowNum,
                'student_id' => $student->id,
                'admission_number' => $admission,
                'student_name' => $name,
                'cells' => $cells,
                'error' => null,
            ];
        }

        fclose($handle);

        if ($stagedRows === []) {
            return back()->with('error', 'No usable rows found in that file.');
        }

        session()->put("subject_teacher_import_staged.{$offering->id}", [
            'academic_session_id' => $request->integer('academic_session_id'),
            'term_id' => $request->integer('term_id'),
            'rows' => $stagedRows,
        ]);

        return redirect()->route('subject-teacher.results.import.preview', $offering);
    }

    public function importPreview(SubjectOffering $offering): View|\Illuminate\Http\RedirectResponse
    {
        $this->authorizeOffering($offering);

        $staged = session("subject_teacher_import_staged.{$offering->id}");

        if (! $staged) {
            return redirect()
                ->route('subject-teacher.results.import', $offering)
                ->with('error', 'Nothing to preview — upload a file first.');
        }

        $offering->loadMissing('subject');

        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user())->load('components');

        return view('subject-teacher.results.import-preview', [
            'offering' => $offering,
            'scheme' => $scheme,
            'rows' => collect($staged['rows']),
            'selectedSession' => $staged['academic_session_id'],
            'selectedTerm' => $staged['term_id'],
        ]);
    }

    public function importApply(Request $request, SubjectOffering $offering)
    {
        $this->authorizeOffering($offering);

        $user = Auth::user();
        $staged = session("subject_teacher_import_staged.{$offering->id}");

        if (! $staged) {
            return redirect()
                ->route('subject-teacher.results.import', $offering)
                ->with('error', 'Nothing staged to apply — upload a file first.');
        }

        $scheme = $this->schemeService->getOrCreateForOffering($offering, $user)->load('components');

        if (! $this->schemeService->isConsistent($scheme)) {
            return $this->schemeNeedsUpdateRedirect($offering);
        }

        $validComponentIds = $scheme->components->pluck('id')->map(fn ($id) => (int) $id)->all();

        $studentArms = Student::whereIn('id', collect($staged['rows'])->pluck('student_id')->filter())
            ->pluck('arm_id', 'id');

        $updated = 0;
        $skipped = 0;
        $rowErrors = [];

        foreach ($staged['rows'] as $row) {
            $label = $row['student_name'] ?? ($row['admission_number'] ?? '');
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
                    ['subject_offering_id' => $offering->id, 'student_id' => $row['student_id']],
                    [
                        'student_id' => $row['student_id'],
                        'academic_session_id' => $staged['academic_session_id'],
                        'term_id' => $staged['term_id'],
                        'subject_offering_id' => $offering->id,
                        'class_id' => $offering->class_id,
                        'arm_id' => $studentArms->get($row['student_id']),
                    ],
                    $componentScores
                );
                $updated++;
            } catch (ValidationException $e) {
                $rowErrors[] = "{$prefix}: ".implode(' ', $e->validator->errors()->all());
                $skipped++;
            }
        }

        session()->forget("subject_teacher_import_staged.{$offering->id}");

        return redirect()
            ->route('subject-teacher.results.import', [
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

    private function schemeNeedsUpdateRedirect(SubjectOffering $offering)
    {
        return redirect()
            ->route('subject-teacher.results.scheme', $offering)
            ->with('error', "The institution's CA/Exam weights changed, so this assessment scheme needs updating before scores can be imported.");
    }

    public function importDiscard(SubjectOffering $offering)
    {
        $this->authorizeOffering($offering);

        session()->forget("subject_teacher_import_staged.{$offering->id}");

        return redirect()
            ->route('subject-teacher.results.import', $offering)
            ->with('error', 'Import discarded — nothing was saved.');
    }
}
