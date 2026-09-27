<?php

namespace App\Services\Results;

use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentResult;
use Illuminate\Support\Collection;

class ResultPositionService
{
    /**
     * Calculate competition-ranking positions.
     *
     * Example:
     * 870 => 1
     * 821 => 2
     * 821 => 2
     * 790 => 4
     */
    public function competitionPositions(Collection $scores): array
    {
        $ordered = $scores
            ->map(fn ($score, $key) => [
                'key' => $key,
                'score' => (float) $score,
            ])
            ->sortByDesc('score')
            ->values();

        $positions = [];
        $previousScore = null;
        $position = 0;

        foreach ($ordered as $index => $item) {
            $position = $previousScore === $item['score']
                ? $position
                : $index + 1;

            $positions[$item['key']] = $position;
            $previousScore = $item['score'];
        }

        return $positions;
    }

    /**
     * Calculate positions for students in a basic-education class context.
     *
     * Positions are calculated at generation time and are never written
     * back into student_results.
     */
    public function classPositions(
        Institution $institution,
        int $academicSessionId,
        int $termId,
        int $classId,
        ?int $armId = null
    ): array {
        $query = StudentResult::query()
            ->where('institution_id', $institution->id)
            ->where('academic_session_id', $academicSessionId)
            ->where('term_id', $termId)
            ->where('class_id', $classId)
            ->whereNotNull('student_id')
            ->whereIn('status', ['approved', 'published', 'locked'])
            ->selectRaw('student_id, SUM(total_score) AS total_score')
            ->groupBy('student_id');

        if ($armId !== null) {
            $query->where('arm_id', $armId);
        }

        $rows = $query->get();

        return $this->competitionPositions(
            $rows->pluck('total_score', 'student_id')
        );
    }

    /**
     * Calculate subject positions inside a class/arm.
     */
    public function subjectPositions(
        Institution $institution,
        int $academicSessionId,
        int $termId,
        int $classId,
        ?int $armId,
        int $subjectOfferingId
    ): array {
        $query = StudentResult::query()
            ->where('institution_id', $institution->id)
            ->where('academic_session_id', $academicSessionId)
            ->where('term_id', $termId)
            ->where('class_id', $classId)
            ->where('subject_offering_id', $subjectOfferingId)
            ->whereIn('status', ['approved', 'published', 'locked'])
            ->select(['student_id', 'total_score']);

        if ($armId !== null) {
            $query->where('arm_id', $armId);
        }

        return $this->competitionPositions(
            $query->get()->pluck('total_score', 'student_id')
        );
    }

    /**
     * Calculate a single student's class position.
     */
    public function studentClassPosition(
        Institution $institution,
        Student $student,
        int $academicSessionId,
        int $termId
    ): ?int {
        if (! $student->class_id) {
            return null;
        }

        $positions = $this->classPositions(
            $institution,
            $academicSessionId,
            $termId,
            (int) $student->class_id,
            $student->arm_id ? (int) $student->arm_id : null
        );

        return $positions[$student->id] ?? null;
    }
}
