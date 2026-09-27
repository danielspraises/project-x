<?php

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use App\Models\ResultAuditLog;
use App\Models\ResultSubmission;
use App\Models\ResultVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

use App\Notifications\ResultReturnedNotification;

class HodController extends Controller
{
    /**
     * List result submissions from the HOD's department that are awaiting verification.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $submissions = ResultSubmission::query()
            ->where('institution_id', $user->institution_id)
            ->where('status', 'submitted')
            ->whereHas('courseOffering.course', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            })
            ->with([
                'courseOffering.course',
                'courseOffering.programme',
                'submittedBy',
                'verifications',
            ])
            ->orderBy('submitted_at')
            ->get();

        return view('hod.results.index', compact('submissions'));
    }

    /**
     * Record an HOD decision (approve/return) on a department's result submission.
     */
    public function verify(Request $request, ResultSubmission $submission)
    {
        $user = Auth::user();

        // Tenant scoping: submission must belong to the HOD's own institution.
        if ($submission->institution_id !== $user->institution_id) {
            abort(403, 'This submission does not belong to your institution.');
        }

        // Department scoping: an HOD may only verify submissions from courses
        // in their own department. Nothing upstream enforces this yet.
        $submission->loadMissing('courseOffering.course');
        $submissionDepartmentId = $submission->courseOffering?->course?->department_id;

        if (! $submissionDepartmentId || $submissionDepartmentId !== $user->department_id) {
            abort(403, 'You can only verify submissions from your own department.');
        }

        // A submission must actually be awaiting review to act on it.
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
                'level' => 'hod',
                'decision' => $validated['decision'],
                'notes' => $validated['notes'] ?? null,
                'reviewed_at' => now(),
            ]);

            // if ($validated['decision'] === 'returned') {
            //     $submission->update([
            //         'status' => 'returned',
            //         'returned_at' => now(),
            //     ]);
            // }


            // Notification
            if ($validated['decision'] === 'returned') {
                $submission->update([
                    'status' => 'returned',
                    'returned_at' => now(),
                ]);

                $submission->loadMissing([
                    'submittedBy',
                    'courseOffering.course',
                ]);

                if ($submission->submittedBy) {
                    $submission->submittedBy->notify(
                        new ResultReturnedNotification(
                            $submission,
                            $validated['notes'] ?? ''
                        )
                    );
                }
            }

            

            ResultAuditLog::create([
                'institution_id' => $submission->institution_id,
                'student_result_id' => null,
                'result_submission_id' => $submission->id,
                'user_id' => $user->id,
                'action' => 'hod_verification_'.$validated['decision'],
                'reason' => $validated['notes'] ?? null,
                'old_values' => ['status' => $previousStatus],
                'new_values' => ['status' => $submission->fresh()->status],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        $message = $validated['decision'] === 'approved'
            ? 'Submission approved and forwarded for ICT review.'
            : 'Submission returned to the lecturer for correction.';

        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }
}
