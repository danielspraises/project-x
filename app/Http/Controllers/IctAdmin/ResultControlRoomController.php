<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ResultSubmission;
use App\Models\Term;
use App\Services\Results\ResultWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ResultControlRoomController extends Controller
{
    public function __construct(private readonly ResultWorkflowService $workflow)
    {
    }

    public function index(Request $request): View
    {
        $user = Auth::user();
        $institutionId = (int) $user->institution_id;

        $sessions = AcademicSession::where('institution_id', $institutionId)
            ->orderByDesc('start_date')->get();

        $selectedSession = $request->integer('academic_session_id') ?: $sessions->first()?->id;
        $terms = Term::where('institution_id', $institutionId)
            ->when($selectedSession, fn ($q) => $q->where('academic_session_id', $selectedSession))
            ->orderBy('order')->get();
        $selectedTerm = $request->integer('term_id') ?: $terms->first()?->id;

        $rows = ResultSubmission::query()
            ->where('result_submissions.institution_id', $institutionId)
            ->when($selectedSession, fn ($q) => $q->where('result_submissions.academic_session_id', $selectedSession))
            ->when($selectedTerm, fn ($q) => $q->where('result_submissions.term_id', $selectedTerm))
            ->when($request->filled('status'), fn ($q) => $q->where('result_submissions.status', $request->string('status')->toString()))
            ->when($request->filled('verification_level'), function ($q) use ($request) {
                $level = $request->string('verification_level')->toString();
                $q->whereHas('verifications', fn ($v) => $v->where('level', $level)->latest('reviewed_at')->limit(1));
            })
            ->with([
                'courseOffering.course',
                'courseOffering.programme.department.faculty',
                'subjectOffering.subject',
                'subjectOffering.class',
                'subjectOffering.arm',
                'submittedBy',
                'verifications.reviewer',
            ])
            ->withCount('verifications')
            ->latest('result_submissions.updated_at')
            ->get();

        $rows = $rows->filter(function ($row) use ($request) {
            $faculty = data_get($row, 'courseOffering.programme.department.faculty.name');
            $department = data_get($row, 'courseOffering.programme.department.name');
            $level = data_get($row, 'courseOffering.level');
            $programme = data_get($row, 'courseOffering.programme.name');
            $courseText = trim((string) data_get($row, 'courseOffering.course.code')) . ' ' . trim((string) data_get($row, 'courseOffering.course.title'));
            $subjectText = trim((string) data_get($row, 'subjectOffering.subject.code')) . ' ' . trim((string) data_get($row, 'subjectOffering.subject.name'));
            $needle = mb_strtolower(trim($request->string('course_search')->toString()));

            return (!$request->filled('faculty') || $faculty === $request->string('faculty')->toString())
                && (!$request->filled('department') || $department === $request->string('department')->toString())
                && (!$request->filled('level') || (string) $level === $request->string('level')->toString())
                && (!$request->filled('programme') || $programme === $request->string('programme')->toString())
                && (!$needle || str_contains(mb_strtolower($courseText . ' ' . $subjectText . ' ' . (string) $row->submittedBy?->name), $needle));
        })->values();

        $all = ResultSubmission::where('institution_id', $institutionId)
            ->when($selectedSession, fn ($q) => $q->where('academic_session_id', $selectedSession))
            ->when($selectedTerm, fn ($q) => $q->where('term_id', $selectedTerm));

        $stats = [
            'total_courses' => (clone $all)->count(),
            'submitted' => (clone $all)->where('status', 'submitted')->count(),
            'pending_ict' => (clone $all)->where('status', 'submitted')->whereHas('verifications', fn ($q) => $q->where('level', 'hod')->where('decision', 'approved'))->count(),
            'published' => (clone $all)->whereIn('status', ['published', 'locked'])->count(),
        ];

        $faculties = $rows->map(fn ($r) => data_get($r, 'courseOffering.programme.department.faculty.name'))->filter()->unique()->sort()->values();
        $departments = $rows->map(fn ($r) => data_get($r, 'courseOffering.programme.department.name'))->filter()->unique()->sort()->values();
        $levels = $rows->map(fn ($r) => data_get($r, 'courseOffering.level'))->filter()->unique()->sort()->values();
        $programmes = $rows->map(fn ($r) => data_get($r, 'courseOffering.programme.name'))->filter()->unique()->sort()->values();

        $alerts = DB::table('result_alerts')
            ->where('institution_id', $institutionId)
            ->where('resolved', false)
            ->latest()->limit(5)->get();

        return view('ict-admin.results.control-room', compact(
            'sessions', 'terms', 'selectedSession', 'selectedTerm', 'rows', 'stats',
            'faculties', 'departments', 'levels', 'programmes', 'alerts'
        ));
    }

    public function batchPublish(Request $request)
    {
        $user = Auth::user();
        $ids = collect($request->input('submission_ids', []))->map(fn ($id) => (int) $id)->filter()->unique();

        if ($ids->isEmpty()) {
            return back()->withErrors(['submission_ids' => 'Select at least one verified submission.']);
        }

        foreach (ResultSubmission::where('institution_id', $user->institution_id)->whereIn('id', $ids)->get() as $submission) {
            $hodApproved = $submission->verifications()->where('level', 'hod')->where('decision', 'approved')->exists();
            if ($submission->status === 'submitted' && $hodApproved) {
                $this->workflow->review($user, $submission, ResultWorkflowService::LEVEL_ICT, ResultWorkflowService::DECISION_APPROVED, 'Batch publication from Results Control Room.');
            }
        }

        return back()->with('success', 'Eligible verified submissions have been published and locked.');
    }

    public function batchLock(Request $request)
    {
        $user = Auth::user();
        $ids = collect($request->input('submission_ids', []))->map(fn ($id) => (int) $id)->filter()->unique();
        $count = ResultSubmission::where('institution_id', $user->institution_id)->whereIn('id', $ids)->where('status', 'published')->update(['status' => 'locked', 'locked_at' => now()]);

        return back()->with('success', "{$count} published submission(s) locked.");
    }
}
