<?php

namespace App\Http\Controllers\ClassTeacher;

use App\Http\Controllers\Controller;
use App\Models\ResultAuditLog;
use App\Models\ResultSubmission;
use App\Models\ResultVerification;
use App\Services\Results\ResultEntryContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClassTeacherController extends Controller
{
    public function __construct(
        private readonly ResultEntryContext $context,
    ) {
    }

    /**
     * List submitted subject-offering results awaiting this class teacher's
     * review. Scoped via the class_teacher_assignments table (there's no
     * static "class_id" on the user record — a class teacher's assignment
     * is per session/term, same table used for the entry-fallback check).
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $submissions = ResultSubmission::query()
            ->where('institution_id', $user->institution_id)
            ->where('status', 'submitted')
            ->whereNotNull('subject_offering_id')
            ->with([
                'subjectOffering.subject',
                'subjectOffering.schoolClass',
                'subjectOffering.arm',
                'submittedBy',
                'verifications',
            ])
            ->orderBy('submitted_at')
            ->get()
            ->filter(fn (ResultSubmission $s) => $s->subjectOffering && $this->context->isClassTeacherFor($user, $s->subjectOffering))
            ->values();

        return view('class-teacher.results.index', compact('submissions'));
    }

    /**
     * Record a class teacher decision (approve/return) on a subject
     * offering's result submission.
     */
    public function verify(Request $request, ResultSubmission $submission)
    {
        $user = Auth::user();

        // Tenant scoping.
        if ($submission->institution_id !== $user->institution_id) {
            abort(403, 'This submission does not belong to your institution.');
        }

        // Class scoping: only the class teacher for this offering's
        // class/arm/session/term may verify it.
        $submission->loadMissing('subjectOffering');

        if (! $submission->subjectOffering || ! $this->context->isClassTeacherFor($user, $submission->subjectOffering)) {
            abort(403, 'You can only verify submissions from your own class.');
        }

        if ($submission->status !== 'submitted') {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Only submitted results can be verified. This submission is currently "'.$submission->status.'".',
                ], 422);
            }

            return back()->with('error', 'Only submitted results can be verified. This submission is currently "'.$submission->status.'".');
        }

        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:approved,returned'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($submission, $validated, $user) {
            $previousStatus = $submission->status;

            ResultVerification::create([
                'institution_id' => $submission->institution_id,
                'result_submission_id' => $submission->id,
                'reviewer_id' => $user->id,
                'level' => 'class_teacher',
                'decision' => $validated['decision'],
                'notes' => $validated['notes'] ?? null,
                'reviewed_at' => now(),
            ]);

            if ($validated['decision'] === 'returned') {
                $submission->update([
                    'status' => 'returned',
                    'returned_at' => now(),
                ]);
            }

            ResultAuditLog::create([
                'institution_id' => $submission->institution_id,
                'student_result_id' => null,
                'result_submission_id' => $submission->id,
                'user_id' => $user->id,
                'action' => 'class_teacher_verification_'.$validated['decision'],
                'reason' => $validated['notes'] ?? null,
                'old_values' => ['status' => $previousStatus],
                'new_values' => ['status' => $submission->fresh()->status],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        $message = $validated['decision'] === 'approved'
            ? 'Submission approved and forwarded for ICT review.'
            : 'Submission returned to the subject teacher for correction.';

        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }
}
