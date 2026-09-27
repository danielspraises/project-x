<?php

namespace App\Services\Results;

use App\Models\Institution;

class ResultPresentationService
{
    public function settings(Institution $institution): array
    {
        return array_merge([
            'show_class_position' => true,
            'show_arm_position' => true,
            'show_subject_position' => true,
            'show_overall_position' => true,
            'show_grade' => true,
            'show_grade_point' => true,
            'show_teacher_remark' => true,
            'show_principal_remark' => true,
            'show_attendance' => true,
            'show_behaviour' => true,
        ], $institution->setting('result_presentation', []));
    }

    public function positionsEnabled(Institution $institution): bool
    {
        return $institution->hasFeature('results.positions');
    }

    public function canGenerateReportCard(Institution $institution): bool
    {
        return $institution->hasFeature('results.report_cards');
    }

    public function canGenerateTranscript(Institution $institution): bool
    {
        return $institution->hasFeature('results.transcripts');
    }
}
