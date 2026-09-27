<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\ResultCorrection;
use App\Models\ResultSubmission;
use App\Models\StudentResult;
use App\Services\Results\ResultCorrectionService;
use App\Services\Results\ResultWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResultWorkflowController extends Controller
{
    public function __construct(
        private readonly ResultWorkflowService $workflow,
        private readonly ResultCorrectionService $corrections,
    ) {
    }

    public function submit(Request $request, ResultSubmission $submission)
    {
        $this->assertRouteInstitution($submission);
        $this->workflow->submit(
            Auth::user(),
            $submission,
            $request->string('submission_note')->toString() ?: null
        );

        return back()->with('success', 'Result submission sent for review.');
    }

    public function review(Request $request, ResultSubmission $submission)
    {
        $this->assertRouteInstitution($submission);

        $data = $request->validate([
            'level' => ['required', 'in:hod,class_teacher,ict'],
            'decision' => ['required', 'in:approved,rejected'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->workflow->review(
            Auth::user(),
            $submission,
            $data['level'],
            $data['decision'],
            $data['notes'] ?? null
        );

        return back()->with('success', 'Result workflow decision recorded.');
    }

    public function requestCorrection(Request $request, StudentResult $result)
    {
        $this->assertRouteInstitution($result);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:5000'],
        ]);

        $this->corrections->request(Auth::user(), $result, $data['reason']);

        return back()->with('success', 'Correction request created.');
    }

    public function releaseCorrection(RequestCorrectionRequest $request, ResultCorrection $correction)
    {
        $this->assertRouteInstitution($correction);
        $this->corrections->authorizeEditing(Auth::user(), $correction);

        return back()->with('success', 'Result released for controlled editing.');
    }

    public function approveCorrection(Request $request, ResultCorrection $correction)
    {
        $this->assertRouteInstitution($correction);

        $data = $request->validate([
            'stage' => ['required', 'in:hod,ict'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($data['stage'] === 'hod') {
            $this->corrections->hodApprove(Auth::user(), $correction, $data['note'] ?? null);
        } else {
            $this->corrections->ictApprove(Auth::user(), $correction, $data['note'] ?? null);
        }

        return back()->with('success', 'Correction approval recorded.');
    }

    public function rejectCorrection(Request $request, ResultCorrection $correction)
    {
        $this->assertRouteInstitution($correction);

        $data = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $this->corrections->reject(Auth::user(), $correction, $data['note']);

        return back()->with('success', 'Correction returned/rejected.');
    }

    private function assertRouteInstitution(object $model): void
    {
        if ((int) Auth::user()->institution_id !== (int) $model->institution_id) {
            abort(403);
        }
    }
}
