<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Arm;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectOffering;
use App\Models\Term;
use App\Models\User;
use App\Services\SubjectRegistrationAutoEnroller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubjectOfferingController extends Controller
{
    public function __construct(
        private readonly SubjectRegistrationAutoEnroller $autoEnroller,
    ) {
    }

    public function index(Request $request): View
    {
        $this->ensureCanManage();

        $sessions = AcademicSession::with('terms')->orderByDesc('start_date')->get();
        $classes = SchoolClass::orderBy('order')->orderBy('name')->get();

        $institutionId = Auth::user()->institution_id;
        $sessionId = $request->integer('academic_session_id');
        $termId = $request->integer('term_id');
        $classId = $request->integer('class_id');

        $offerings = SubjectOffering::with(['academicSession', 'term', 'schoolClass', 'arm', 'subject', 'teachers'])
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->when($classId,fn ($q) => $q->where('class_id', $classId))
            ->orderByDesc('academic_session_id')
            ->orderBy('term_id')
            ->orderBy('class_id')
            ->orderBy('scope')
            ->orderBy('subject_id')
            ->get();

        return view('ict-admin.subject-offerings.index', compact(
            'offerings', 'sessions', 'classes', 'sessionId', 'termId', 'classId', 'institutionId'
        ));
    }

    public function classView(Request $request, SchoolClass $schoolClass): View
    {
        $this->ensureCanManage();
        $sessions = AcademicSession::with('terms')->orderByDesc('start_date')->get();
        $institution = Auth::user()->institution;
        $sessionId = (int) ($request->integer('academic_session_id') ?: $institution->setting('default_academic_session_id', $sessions->first()?->id));
        $session = $sessions->firstWhere('id', $sessionId) ?: $sessions->first();
        $termId = (int) ($request->integer('term_id') ?: $institution->setting('default_term_id', $session?->terms->first()?->id));
        $term = $session?->terms->firstWhere('id', $termId) ?: $session?->terms->first();

        $offerings = collect();
        if ($session && $term) {
            $offerings = SubjectOffering::with(['subject', 'arm', 'teachers'])
                ->where('class_id', $schoolClass->id)
                ->where('academic_session_id', $session->id)
                ->where('term_id', $term->id)
                ->orderBy('scope')->orderBy('subject_id')->get();
        }
        $subjects = Subject::orderBy('name')->get();
        $teachers = $this->teacherQuery()->orderBy('name')->get();
        $defaultScope = $institution->setting('subject_offering_default_scope', 'class');
        return view('ict-admin.subject-offerings.class', compact('schoolClass', 'sessions', 'session', 'term', 'offerings', 'subjects', 'teachers', 'defaultScope'));
    }

    public function teacherSearch(Request $request): JsonResponse
    {
        $this->ensureCanManage();

        $term = trim((string) $request->input('q', ''));
        abort_if(mb_strlen($term) > 100, 422, 'Search text is too long.');

        $teachers = $this->teacherQuery()
            ->select(['users.id', 'users.name', 'users.email', 'users.role_id'])
            ->with('role:id,name,slug')
            ->when($term !=='', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('users.name', 'like', '%' .$term . '%')
                      ->orWhere('users.email', 'like', '%' . $term . '%');
                });
            })
            ->orderBy('users.name')
            ->limit(25)
            ->get()
            ->map(fn ($teacher) => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'email' => $teacher->email,
                'role' => $teacher->role?->name,
            ]);

        return response()->json(['data' => $teachers]);
    }

    public function subjectSearch(Request $request): JsonResponse
    {
        $this->ensureCanManage();

        $term = trim((string) $request->input('q', ''));
        abort_if(mb_strlen($term) > 100, 422, 'Search text is too long.');

        if ($term === '' || mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $subjects = Subject::query()
            ->where('subjects.institution_id', Auth::user()->institution_id)
            ->where(function($query) use ($term) {
                $query->where('subjects.name', 'like', '%' . $term . '%')
                    ->orWhere('subjects.code', 'like', '%' . $term . '%');
            })
            ->select(['subjects.id', 'subjects.name', 'subjects.code'])
            ->orderBy('subjects.name')
            ->limit(25)
            ->get()
            ->map(fn ($subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
                'label' => ($subject->code ? $subject->code . ' — ' : '') . $subject->name,
            ]);

        return response()->json(['data' => $subjects]);
    }

    public function create(Request $request): View
    {
        $this->ensureCanManage();

        $oldSubjectId = $request->session()->getOldInput('subject_id');
        $selectedSubject = $oldSubjectId
            ? Subject::where('institution_id', Auth::user()->institution_id)->find($oldSubjectId)
            : null;

        return view('ict-admin.subject-offerings.create',array_merge(
            [
                'subjectOffering' => new SubjectOffering(),
                'selectedSubject' => $selectedSubject,
            ],
            $this->formData()
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManage();
        $validated = $this->validated($request);
        $this->ensureScope($validated);
        $this->ensureNoOppositeScope($validated);
        $offering = DB::transaction(function () use ($validated) {
            $offering = $this->upsertOffering($validated);
            $this->syncTeachers($offering, $validated['teacher_ids'] ?? [], $validated['lead_teacher_id'] ?? null);
            $this->autoEnroller->enrollForOffering($offering);
            return $offering;
        });
        return $this->backToClass($offering)->with('success', 'Subject offering saved.');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $this->ensureCanManage();
        $data = $request->validate([
            'academic_session_id' => ['required', 'exists:academic_sessions,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'scope' => ['required', 'in:class,arm'],
            'requirement_type' => ['required', 'in:compulsory,elective'],
            'subject_ids' =>['required', 'array', 'min:1'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'arm_ids' => ['nullable', 'array'],
            'arm_ids.*' => ['integer', 'exists:arms,id'],
            'teacher_ids' =>['nullable', 'array'],
            'teacher_ids.*' => ['integer', 'exists:users,id'],
            'lead_teacher_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $this->ensureScope($data, true);
        $created = 0; $updated = 0; $conflicts = [];
        DB::transaction(function () use ($data, &$created, &$updated, &$conflicts) {
            $subjects = Subject::whereIn('id', $data['subject_ids'])->get();
            $targets = $data['scope'] === 'class' ? [null] : array_values(array_unique(array_map('intval', $data['arm_ids'] ?? [])));
            foreach ($subjects as $subject) {
                foreach ($targets as $armId) {
                    $existing = SubjectOffering::where('academic_session_id', $data['academic_session_id'])
                        ->where('term_id', $data['term_id'])->where('class_id', $data['class_id'])
                        ->where('subject_id', $subject->id)->where('scope', $data['scope'])
                        ->when($data['scope'] === 'arm', fn ($q) => $q->where('arm_id', $armId))->first();
                    $opposite = SubjectOffering::where('academic_session_id', $data['academic_session_id'])
                        ->where('term_id', $data['term_id'])->where('class_id', $data['class_id'])
                        ->where('subject_id', $subject->id)->where('scope', $data['scope'] === 'class' ? 'arm' : 'class')->exists();
                    if ($opposite) {
                        $conflicts[] = $subject->name . '(' . ($data['scope'] === 'class' ? 'arm-specific already exists' : 'class-wide alreadyexists') . ')';
                        continue;
                    }
                    $payload= array_merge($data, ['subject_id' => $subject->id, 'arm_id' => $armId]);
                    $offering = $this->upsertOffering($payload);
                    $existing ? $updated++ : $created++;
                    $this->syncTeachers($offering, $data['teacher_ids'] ?? [], $data['lead_teacher_id'] ?? null);
                    $this->autoEnroller->enrollForOffering($offering);
                }
            }
        });
        $msg = "Bulk setup complete: {$created} created, {$updated} updated.";
        if ($conflicts) $msg.= ' Skipped conflicts: ' . implode(', ', array_unique($conflicts));
        return redirect()->route('ict-admin.subject-offerings.class', ['schoolClass' => $data['class_id'], 'academic_session_id' => $data['academic_session_id'], 'term_id' => $data['term_id']])->with('success', $msg);
    }

    public function edit(SubjectOffering $subjectOffering): View
    {
        $this->ensureCanManage();

        $subjectOffering->load(['teachers', 'subject']);

        return view('ict-admin.subject-offerings.edit', array_merge(
            [
                'subjectOffering' => $subjectOffering,
                'selectedSubject' => $subjectOffering->subject,
            ],
            $this->formData()
        ));
    }

    public function update(Request $request, SubjectOffering $subjectOffering): RedirectResponse
    {
        $this->ensureCanManage();
        $validated = $this->validated($request);
        $this->ensureScope($validated, false, $subjectOffering->id);
        $this->ensureNoOppositeScope($validated, $subjectOffering->id);
        DB::transaction(function () use ($subjectOffering, $validated) {
            $offering = $this->upsertOffering($validated,$subjectOffering);
            $this->syncTeachers($offering, $validated['teacher_ids'] ?? [], $validated['lead_teacher_id'] ?? null);
            $this->autoEnroller->enrollForOffering($offering);
        });
        return $this->backToClass($subjectOffering)->with('success', 'Subject offeringupdated.');
    }

    public function destroy(SubjectOffering $subjectOffering): RedirectResponse
    {
        $this->ensureCanManage();
        abort_if($subjectOffering->registrations()->exists(), 422, 'Students are already registered on this offering — remove those registrations first.');
        $classId = $subjectOffering->class_id; $sessionId= $subjectOffering->academic_session_id; $termId = $subjectOffering->term_id;
        $subjectOffering->delete();
        return redirect()->route('ict-admin.subject-offerings.class', ['schoolClass' => $classId, 'academic_session_id' => $sessionId, 'term_id'=> $termId])->with('success', 'Subject offering deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'academic_session_id' => ['required', 'exists:academic_sessions,id'],
            'term_id' => ['required', 'exists:terms,id'],'class_id' => ['required', 'exists:classes,id'],
            'scope' => ['required', 'in:class,arm'], 'arm_id' => ['nullable', 'exists:arms,id'],
            'requirement_type' => ['required', 'in:compulsory,elective'],
            'subject_id' => ['required', 'exists:subjects,id'], 'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer', 'exists:users,id', 'different:lead_teacher_id'], 'lead_teacher_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        if ($data['scope'] === 'arm' && empty($data['arm_id'])) abort(422, 'An arm is required for an arm-specific offering.');
        if ($data['scope'] === 'class') $data['arm_id'] =null;
        return $data;
    }

    private function ensureScope(array $data, bool $bulk = false, ?int $ignoreId = null): void
    {
        $institutionId = Auth::user()->institution_id;
        $session = AcademicSession::findOrFail($data['academic_session_id']); $term = Term::findOrFail($data['term_id']); $class = SchoolClass::findOrFail($data['class_id']);
        abort_if($session->institution_id !== $institutionId, 403);
        abort_if($term->institution_id !== $institutionId|| $term->academic_session_id !== $session->id, 422, 'Selected term does not belong tothe selected session.');
        abort_if($class->institution_id !== $institutionId, 403);
        $armIds = $bulk ? ($data['arm_ids'] ?? []) : array_filter([$data['arm_id'] ?? null]);
        if ($data['scope'] === 'arm') abort_if(Arm::whereIn('id', $armIds)->where('institution_id', $institutionId)->where('class_id', $class->id)->count() !== count(array_unique($armIds)), 422, 'One ormore selected arms do not belong to this class.');
        $subjectIds = $bulk ? ($data['subject_ids'] ?? []) : [$data['subject_id']];
        abort_if(Subject::whereIn('id', $subjectIds)->where('institution_id', $institutionId)->count() !== count(array_unique($subjectIds)), 422, 'One or more selected subjects are outside this institution.');
        $teacherIds = array_unique(array_merge($data['teacher_ids'] ?? [], array_filter([$data['lead_teacher_id'] ?? null])));
        if ($teacherIds) abort_if($this->teacherQuery()->whereIn('users.id', $teacherIds)->count() !== count($teacherIds), 422, 'One or more selected teachers cannot be assigned.');
    }

    private function upsertOffering(array $data, ?SubjectOffering $existing = null): SubjectOffering
    {
        $scopeKey = $data['scope'] === 'class'
            ? 'class:' . $data['class_id'] . ':subject:' . $data['subject_id']
            : 'arm:' . $data['arm_id'] . ':subject:' . $data['subject_id'];
        $query = SubjectOffering::where('academic_session_id', $data['academic_session_id'])->where('term_id', $data['term_id'])->where('scope_key', $scopeKey);
        if ($existing) $query->where('id', '!=', $existing->id);
        $found = $query->first();
        if ($found) {
            throw ValidationException::withMessages([
                'subject_id'=> 'That subject offering already exists for this session, period and scope.',
            ]);
        }
        $payload = [
            'institution_id'=> Auth::user()->institution_id, 'academic_session_id' =>$data['academic_session_id'], 'term_id' => $data['term_id'],
            'class_id' => $data['class_id'], 'arm_id' => $data['arm_id'] ?? null, 'subject_id' => $data['subject_id'], 'scope' => $data['scope'], 'scope_key' => $scopeKey,
            'requirement_type' => $data['requirement_type'],
        ];
        if ($existing) { $existing->update($payload); return $existing; }
        return SubjectOffering::create($payload);
    }

    private function ensureNoOppositeScope(array $data, ?int $ignoreId = null): void
    {
        $query = SubjectOffering::where('academic_session_id', $data['academic_session_id'])
            ->where('term_id', $data['term_id'])->where('class_id', $data['class_id'])
            ->where('subject_id', $data['subject_id'])->where('scope', $data['scope'] === 'class' ? 'arm' : 'class');
        if ($ignoreId) $query->where('id', '!=', $ignoreId);
        if ($data['scope'] === 'arm') $query->where('scope', 'class');
        if ($query->exists()) {
            throw ValidationException::withMessages([
                'scope' => 'This subject already has the opposite scope in this session and period. Use one scope for a subject in a class, or remove the conflicting offering first.',
            ]);
        }
    }

    private function syncTeachers(SubjectOffering $offering, array $teacherIds, ?int $leadTeacherId): void
    {
        $ids = array_values(array_unique(array_map('intval', $teacherIds)));
        if ($leadTeacherId && !in_array((int)$leadTeacherId, $ids, true)) $ids[] = (int)$leadTeacherId;
        $sync = [];
        foreach ($ids as $id) $sync[$id] = ['role' => $id=== (int)$leadTeacherId ? 'lead' : 'supporting'];
        $offering->teachers()->sync($sync);
    }

    private function backToClass(SubjectOffering $offering): RedirectResponse { return redirect()->route('ict-admin.subject-offerings.class', ['schoolClass' => $offering->class_id, 'academic_session_id'=> $offering->academic_session_id, 'term_id' => $offering->term_id]); }
    private function ensureCanManage(): void { abort_unless(Auth::user()->hasPermission('subjects.manage'), 403); }
    private function teacherQuery() { return User::query()->where('users.institution_id', Auth::user()->institution_id)->whereHas('role', fn($r)=> $r->whereIn('slug', ['class_teacher', 'subject_teacher'])); }
    private function formData(): array
    {
        return [
            'sessions' => AcademicSession::with('terms')->orderByDesc('start_date')->get(),
            'classes' => SchoolClass::with('arms')->orderBy('order')->get(),
            'teacherSearchUrl' => route('ict-admin.subject-offerings.teachers.search'),
            'subjectSearchUrl' => route('ict-admin.subject-offerings.subjects.search'),
            'defaultScope' => Auth::user()->institution->setting('subject_offering_default_scope', 'class'),
        ];
    }
}
