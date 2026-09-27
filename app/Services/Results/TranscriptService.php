<?php

namespace App\Services\Results;

use App\Models\Institution;
use App\Models\Student;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TranscriptService
{
    public function __construct(
        private readonly ResultPresentationService $presentation,
    ) {
    }

    /**
     * Build the tertiary transcript view model.
     *
     * This reads the existing course registration/result structure and does
     * not mutate student results.
     */
    public function generate(
        Institution $institution,
        Student $student
    ): array {
        if (! $this->presentation->canGenerateTranscript($institution)) {
            abort(403, 'Transcript generation is not enabled for this institution.');
        }

        $assignment = $institution->transcriptTemplate()
            ->with('template')
            ->first();

        if (! $assignment || ! $assignment->template || ! $assignment->template->is_active) {
            throw (new ModelNotFoundException)->setModel(
                $assignment ? $assignment->template::class : 'App\\Models\\TranscriptTemplate'
            );
        }

        $registrations = $student->courseRegistrations()
            ->with([
                'courseOffering.course',
                'courseOffering.programme',
                'courseOffering.term',
            ])
            ->get();

        $results = $student->results()
            ->where('institution_id', $institution->id)
            ->whereIn('status', ['approved', 'published', 'locked'])
            ->whereNotNull('course_registration_id')
            ->get()
            ->keyBy('course_registration_id');

        $courses = $registrations->map(function ($registration) use ($results) {
            $result = $results->get($registration->id);
            $offering = $registration->courseOffering;
            $course = $offering?->course;

            return [
                'registration' => $registration,
                'result' => $result,
                'course' => $course,
                'course_code' => $course?->code,
                'course_title' => $course?->title,
                'credit_unit' => $course?->credit_unit,
                'grade' => $result?->grade,
                'grade_point' => $result?->grade_point,
                'total_score' => $result?->total_score,
                'term' => $offering?->term,
                'programme' => $offering?->programme,
            ];
        });

        $semesterGroups = $courses->groupBy(
            fn (array $row) => $row['result']?->academic_session_id . ':' . $row['result']?->term_id
        );

        $semesters = $semesterGroups->map(function ($rows) {
            $creditUnits = $rows->sum(
                fn (array $row) => (float) ($row['credit_unit'] ?? 0)
            );

            $qualityPoints = $rows->sum(function (array $row) {
                return (float) ($row['credit_unit'] ?? 0)
                    * (float) ($row['grade_point'] ?? 0);
            });

            return [
                'rows' => $rows->values(),
                'total_credit_units' => $creditUnits,
                'total_quality_points' => $qualityPoints,
                'gpa' => $creditUnits > 0
                    ? round($qualityPoints / $creditUnits, 2)
                    : 0,
            ];
        })->values();

        $totalCredits = $semesters->sum('total_credit_units');
        $totalQualityPoints = $semesters->sum('total_quality_points');

        return [
            'institution' => $institution,
            'student' => $student,
            'template' => $assignment->template,
            'settings' => $this->presentation->settings($institution),
            'courses' => $courses->values(),
            'semesters' => $semesters,
            'cumulative' => [
                'total_credit_units' => $totalCredits,
                'total_quality_points' => $totalQualityPoints,
                'cgpa' => $totalCredits > 0
                    ? round($totalQualityPoints / $totalCredits, 2)
                    : 0,
            ],
        ];
    }
}
