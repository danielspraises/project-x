<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Student;
use App\Services\Results\TranscriptService;
use Illuminate\Http\Request;

class TranscriptController extends Controller
{
    public function __construct(
        private readonly TranscriptService $transcripts,
    ) {
    }

    public function index(Request $request)
    {
        $institution = $request->user()->institution;

        abort_unless($institution instanceof Institution, 403);

        abort_unless(
            $institution->education_level === 'tertiary',
            404
        );

        abort_unless(
            $institution->hasFeature('results.transcripts'),
            403
        );

        $students = Student::query()
            ->where('institution_id', $institution->id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(200)
            ->get();

        return view('ict-admin.results.transcripts.index', compact(
            'institution',
            'students',
        ));
    }

    public function show(Request $request, Student $student)
    {
        $institution = $request->user()->institution;

        abort_unless($institution instanceof Institution, 403);
        abort_unless((int) $student->institution_id === (int) $institution->id, 404);

        abort_unless(
            $institution->education_level === 'tertiary',
            404
        );

        abort_unless(
            $institution->hasFeature('results.transcripts'),
            403
        );

        return view(
            'ict-admin.results.transcripts.show',
            $this->transcripts->generate($institution, $student)
        );
    }
}
