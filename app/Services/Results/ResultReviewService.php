<?php

namespace App\Services\Results;

use App\Models\AssessmentScheme;
use App\Models\AssessmentScore;
use App\Models\ResultSubmission;
use App\Models\StudentResult;

/**
 * Builds the read-only view of one submission that a HOD or Class Teacher
 * reviews before approving or returning it: every student's score for each
 * assessment component, their CA / exam / total / grade, and a few summary
 * figures. It never writes anything.
 */
class ResultReviewService
{
    public function __construct(
        private readonly AssessmentCsvHelper $format,
    ) {
    }

    /**
     * @return array{rows: \Illuminate\Support\Collection, components: \Illuminate\Support\Collection, scheme: AssessmentScheme|null, stats: array}
     */
    public function build(ResultSubmission $submission): array
    {
        $results = StudentResult::where('institution_id', $submission->institution_id)
            ->where('result_submission_id', $submission->id)
            ->with('student')
            ->get()
            ->filter(fn (StudentResult $r) => $r->student !== null)
            ->sortBy([
                fn ($r) => $r->student->last_name,
                fn ($r) => $r->student->first_name,
            ])
            ->values();

        $scheme = null;

        if ($submission->course_offering_id) {
            $scheme = AssessmentScheme::where('course_offering_id', $submission->course_offering_id)->with('components')->first();
        } elseif ($submission->subject_offering_id) {
            $scheme = AssessmentScheme::where('subject_offering_id', $submission->subject_offering_id)->with('components')->first();
        }

        // A submission made before assessment schemes existed has no
        // component scores — the table then just shows CA / Exam / Total.
        $components = $scheme ? $scheme->components : collect();

        $scores = AssessmentScore::whereIn('student_result_id', $results->pluck('id'))
            ->get()
            ->groupBy('student_result_id')
            ->map(fn ($rows) => $rows->keyBy('assessment_component_id'));

        $rows = $results->map(function (StudentResult $result) use ($components, $scores) {
            $rowScores = $scores->get($result->id) ?? collect();

            $cells = [];
            foreach ($components as $component) {
                $cells[$component->id] = $this->format->describe($rowScores->get($component->id));
            }

            return [
                'name' => $result->student->last_name.', '.$result->student->first_name,
                'identifier' => $result->student->matric_number ?: $result->student->admission_number,
                'cells' => $cells,
                'ca' => $this->number($result->ca_score),
                'exam' => $this->number($result->exam_score),
                'total' => $this->number($result->total_score),
                'total_value' => $result->total_score === null ? null : (float) $result->total_score,
                'grade' => $result->grade,
            ];
        })->values();

        $totals = $rows->pluck('total_value')->filter(fn ($v) => $v !== null);

        $stats = [
            'count' => $rows->count(),
            'incomplete' => $rows->count() - $totals->count(),
            'average' => $totals->isEmpty() ? null : $this->format->formatNumber(round((float) $totals->avg(), 1)),
            'highest' => $totals->isEmpty() ? null : $this->format->formatNumber((float) $totals->max()),
            'lowest' => $totals->isEmpty() ? null : $this->format->formatNumber((float) $totals->min()),
            'grades' => $rows->pluck('grade')->filter()->countBy()->sortKeys()->all(),
        ];

        return [
            'rows' => $rows,
            'components' => $components,
            'scheme' => $scheme,
            'stats' => $stats,
        ];
    }

    private function number($value): ?string
    {
        return $value === null ? null : $this->format->formatNumber((float) $value);
    }
}
