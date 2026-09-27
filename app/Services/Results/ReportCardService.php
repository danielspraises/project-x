<?php

namespace App\Services\Results;

use App\Models\Institution;
use App\Models\Student;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class ReportCardService
{
    public function __construct(
        private readonly ResultPresentationService $presentation,
        private readonly ResultPositionService $positions,
    ) {
    }

    /**
     * Build a report-card view model.
     *
     * Generation is blocked unless Super Admin has enabled the feature and
     * assigned a report-card template to the institution.
     */
    public function generate(
        Institution $institution,
        Student $student,
        int $academicSessionId,
        int $termId
    ): array {
        if (! $this->presentation->canGenerateReportCard($institution)) {
            abort(403, 'Report card generation is not enabled for this institution.');
        }

        $assignment = $institution->reportCardTemplate()
            ->with('template')
            ->first();

        if (! $assignment || ! $assignment->template || ! $assignment->template->is_active) {
            throw (new ModelNotFoundException)->setModel(
                $assignment ? $assignment->template::class : 'App\\Models\\ReportCardTemplate'
            );
        }

        $settings = $this->presentation->settings($institution);

        $results = $student->results()
            ->where('institution_id', $institution->id)
            ->where('academic_session_id', $academicSessionId)
            ->where('term_id', $termId)
            ->whereIn('status', ['approved', 'published', 'locked'])
            ->with([
                'subjectOffering.subject',
                'class',
                'arm',
            ])
            ->get();

        $subjects = $results->map(fn ($result) => [
            'result' => $result,
            'subject' => $result->subjectOffering?->subject,
            'total' => (float) $result->total_score,
            'grade' => $result->grade,
            'grade_point' => $result->grade_point,
        ]);

        $summary = [
            'subjects_count' => $subjects->count(),
            'total_score' => (float) $subjects->sum('total'),
            'average_score' => $subjects->count()
                ? round($subjects->avg('total'), 2)
                : 0,
        ];

        $positionData = [];

        if ($this->presentation->positionsEnabled($institution)) {
            if ($settings['show_class_position'] ?? false) {
                $positionData['class'] = $this->positions->studentClassPosition(
                    $institution,
                    $student,
                    $academicSessionId,
                    $termId
                );
            }

            if (($settings['show_arm_position'] ?? false) && $student->class_id && $student->arm_id) {
                $armPositions = $this->positions->classPositions(
                    $institution,
                    $academicSessionId,
                    $termId,
                    (int) $student->class_id,
                    (int) $student->arm_id
                );

                $positionData['arm'] = $armPositions[$student->id] ?? null;
            }
        }

        return [
            'institution' => $institution,
            'student' => $student,
            'academic_session_id' => $academicSessionId,
            'term_id' => $termId,
            'template' => $assignment->template,
            'settings' => $settings,
            'subjects' => $subjects,
            'summary' => $summary,
            'positions' => $positionData,
        ];
    }
}
