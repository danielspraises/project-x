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
            'scheme'
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

    public function studentShow(SubjectOffering $offering, Student $student): View
    {
        $this->authorizeOffering($offering);
        $this->authorizeStudent($offering, $student);

        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user())->load('components');

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
                    ->route('subject-teacher.results.students.show', [$offering, $next])
                    ->with('success', 'Score saved.');
            }
        }

        return redirect()
            ->route('subject-teacher.results.students.index', $offering)
            ->with('success', 'Score saved.');
    }

    public function bulk(Request $request, SubjectOffering $offering): View
    {
        $this->authorizeOffering($offering);

        $offering->loadMissing('subject', 'schoolClass', 'arm');

        $scheme = $this->schemeService->getOrCreateForOffering($offering, Auth::user())->load('components');

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
    public function importForm(Request $request, SubjectOffering $offering): View
    {
        $this->authorizeOffering($offering);

        $offering->loadMissing('subject', 'schoolClass', 'arm');

        $selectedSession = $request->integer('academic_session_id') ?: $offering->academic_session_id;
        $selectedTerm = $request->integer('term_id') ?: $offering->term_id;

        return view('subject-teacher.results.import', compact('offering', 'selectedSession', 'selectedTerm'));
    }

    /**
     * Downloadable CSV template, pre-filled with each student's admission
     * number and current scores.
     */
    public function importTemplate(SubjectOffering $offering)
    {
        $this->authorizeOffering($offering);

        $offering->loadMissing('subject');

        $students = $this->context->studentsForBasic(Auth::user(), $offering)
            ->sortBy([fn ($s) => $s->last_name, fn ($s) => $s->first_name])
            ->values();

        $existingResults = StudentResult::where('institution_id', $offering->institution_id)
            ->where('subject_offering_id', $offering->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $filename = 'scores-'.($offering->subject->name ?? $offering->id).'-template.csv';

        return response()->streamDownload(function () use ($students, $existingResults) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['admission_number', 'student_name', 'ca_score', 'exam_score']);

            foreach ($students as $student) {
                $existing = $existingResults->get($student->id);

                fputcsv($out, [
                    $student->admission_number,
                    $student->last_name.', '.$student->first_name,
                    $existing?->ca_score,
                    $existing?->exam_score,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Parse and validate an uploaded CSV, but write nothing yet — staged in
     * the session until confirmed on the preview page (importApply) or
     * discarded (importDiscard). Same "blank cell = leave unchanged" rule
     * as the Lecturer importer.
     */
    public function importUpload(Request $request, SubjectOffering $offering)
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
        foreach (['admission_number', 'ca_score', 'exam_score'] as $column) {
            $pos = array_search($column, $header, true);
            $columnIndex[$column] = $pos === false ? null : $pos;
        }

        if ($columnIndex['admission_number'] === null) {
            fclose($handle);
            return back()->with('error', 'The CSV is missing an "admission_number" column. Please use the downloaded template and don\'t rename its headers.');
        }

        $studentsByAdmission = $this->context->studentsForBasic($user, $offering)
            ->filter(fn ($s) => $s->admission_number !== null)
            ->keyBy(fn ($s) => strtoupper(trim($s->admission_number)));

        $existingResults = StudentResult::where('institution_id', $user->institution_id)
            ->where('subject_offering_id', $offering->id)
            ->whereIn('student_id', $studentsByAdmission->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $stagedRows = [];
        $rowNum = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $admission = strtoupper(trim((string) ($data[$columnIndex['admission_number']] ?? '')));

            if ($admission === '') {
                $stagedRows[] = ['row' => $rowNum, 'error' => 'Blank admission number.'];
                continue;
            }

            $student = $studentsByAdmission->get($admission);

            if (! $student) {
                $stagedRows[] = ['row' => $rowNum, 'admission_number' => $admission, 'error' => 'Admission number not recognized for this subject.'];
                continue;
            }

            $caRaw = $columnIndex['ca_score'] !== null ? trim((string) ($data[$columnIndex['ca_score']] ?? '')) : '';
            $examRaw = $columnIndex['exam_score'] !== null ? trim((string) ($data[$columnIndex['exam_score']] ?? '')) : '';

            if ($caRaw === '' && $examRaw === '') {
                continue;
            }

            if ($caRaw !== '' && ! is_numeric($caRaw)) {
                $stagedRows[] = ['row' => $rowNum, 'admission_number' => $admission, 'student_name' => $student->last_name.', '.$student->first_name, 'error' => "CA score \"{$caRaw}\" is not numeric."];
                continue;
            }

            if ($examRaw !== '' && ! is_numeric($examRaw)) {
                $stagedRows[] = ['row' => $rowNum, 'admission_number' => $admission, 'student_name' => $student->last_name.', '.$student->first_name, 'error' => "Exam score \"{$examRaw}\" is not numeric."];
                continue;
            }

            $existing = $existingResults->get($student->id);

            $newCa = $caRaw !== '' ? (float) $caRaw : $existing?->ca_score;
            $newExam = $examRaw !== '' ? (float) $examRaw : $existing?->exam_score;

            $stagedRows[] = [
                'row' => $rowNum,
                'student_id' => $student->id,
                'admission_number' => $admission,
                'student_name' => $student->last_name.', '.$student->first_name,
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

        return view('subject-teacher.results.import-preview', [
            'offering' => $offering,
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

        $studentArms = Student::whereIn('id', collect($staged['rows'])->pluck('student_id')->filter())
            ->pluck('arm_id', 'id');

        $updated = 0;
        $skipped = 0;
        $rowErrors = [];

        foreach ($staged['rows'] as $row) {
            if (! empty($row['error'])) {
                $label = $row['student_name'] ?? ($row['admission_number'] ?? '');
                $rowErrors[] = "Row {$row['row']}".($label ? " ({$label})" : '').": {$row['error']}";
                $skipped++;
                continue;
            }

            $existing = StudentResult::where('institution_id', $user->institution_id)
                ->where('subject_offering_id', $offering->id)
                ->where('student_id', $row['student_id'])
                ->first();

            $payload = [
                'student_id' => $row['student_id'],
                'academic_session_id' => $staged['academic_session_id'],
                'term_id' => $staged['term_id'],
                'subject_offering_id' => $offering->id,
                'class_id' => $offering->class_id,
                'arm_id' => $studentArms->get($row['student_id']),
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

    public function importDiscard(SubjectOffering $offering)
    {
        $this->authorizeOffering($offering);

        session()->forget("subject_teacher_import_staged.{$offering->id}");

        return redirect()
            ->route('subject-teacher.results.import', $offering)
            ->with('error', 'Import discarded — nothing was saved.');
    }
}
