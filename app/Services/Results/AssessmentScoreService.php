<?php

namespace App\Services\Results;

use App\Models\AssessmentScheme;
use App\Models\AssessmentScore;
use App\Models\StudentResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssessmentScoreService
{
    public function __construct(
        private readonly ResultEntryService $resultEntryService,
        private readonly AssessmentSchemeService $schemeService,
    ) {
    }

    /**
     * Save one student's component scores for one offering and recompute
     * their rolled-up StudentResult (ca_score/exam_score/total_score) from
     * the components. From this point on ca_score/exam_score are never
     * trusted as direct input — only ever the sum of entered components —
     * so every other screen that reads student_results keeps working
     * unchanged.
     *
     * $lookup identifies the StudentResult row — tertiary:
     * ['course_registration_id' => ...]; basic-ed:
     * ['subject_offering_id' => ..., 'student_id' => ...].
     * $context carries the rest of the create/update payload (student_id,
     * academic_session_id, term_id, course_offering_id or
     * subject_offering_id/class_id/arm_id).
     *
     * @param array<int, array{score: float|null, is_absent: bool}> $componentScores keyed by assessment_component_id
     */
    public function saveForStudent(
        User $user,
        AssessmentScheme $scheme,
        array $lookup,
        array $context,
        array $componentScores
    ): StudentResult {
        return DB::transaction(function () use ($user, $scheme, $lookup, $context, $componentScores) {
            $query = StudentResult::where('institution_id', $user->institution_id);

            foreach ($lookup as $column => $value) {
                $query->where($column, $value);
            }

            $existing = $query->first();

            if (! $existing) {
                $existing = $this->resultEntryService->create($user, array_merge($context, [
                    'ca_score' => null,
                    'exam_score' => null,
                ]));
            }

            $scheme->loadMissing('components');

            $anyRealDataEntered = false;

            foreach ($scheme->components as $component) {
                if (! array_key_exists($component->id, $componentScores)) {
                    continue;
                }

                $input = $componentScores[$component->id];
                $score = $input['score'] ?? null;
                $isAbsent = (bool) ($input['is_absent'] ?? false);

                if ($score !== null || $isAbsent) {
                    $anyRealDataEntered = true;
                }

                $scoreRow = AssessmentScore::firstOrNew([
                    'assessment_component_id' => $component->id,
                    'student_result_id' => $existing->id,
                ]);

                if (! $scoreRow->exists) {
                    $scoreRow->institution_id = $user->institution_id;
                    $scoreRow->entered_by = $user->id;
                }

                $scoreRow->score = $score;
                $scoreRow->is_absent = $isAbsent;
                $scoreRow->updated_by = $user->id;
                $scoreRow->save();
            }

            [$ca, $exam] = $this->computeTotals($scheme, $existing);

            $this->resultEntryService->update($user, $existing, array_merge($context, [
                'ca_score' => $ca,
                'exam_score' => $exam,
            ]));

            // A scheme locks the first time real scores land under it — not
            // just because the entry page was opened and saved blank.
            if ($anyRealDataEntered) {
                $this->schemeService->lock($scheme);
            }

            return $existing->fresh();
        });
    }

    /**
     * @return array{0: float|null, 1: float|null} [ca_score, exam_score]
     */
    private function computeTotals(AssessmentScheme $scheme, StudentResult $studentResult): array
    {
        $scores = AssessmentScore::where('student_result_id', $studentResult->id)
            ->whereIn('assessment_component_id', $scheme->components->pluck('id'))
            ->get()
            ->keyBy('assessment_component_id');

        $nonExam = $scheme->components->where('type', '!=', 'exam')->values();
        $examComponent = $scheme->components->firstWhere('type', 'exam');

        $ca = $this->sumIfComplete($nonExam, $scores);
        $exam = $examComponent ? $this->sumIfComplete(collect([$examComponent]), $scores) : null;

        return [$ca, $exam];
    }

    /**
     * Sums the given components' scores only if every one of them has
     * something recorded (a real score or an absent mark) for this student
     * — otherwise returns null, which is exactly the "not complete yet"
     * signal the rest of the app already expects from ca_score/exam_score.
     */
    private function sumIfComplete($components, $scoresByComponent): ?float
    {
        if ($components->isEmpty()) {
            return null;
        }

        $sum = 0.0;

        foreach ($components as $component) {
            $score = $scoresByComponent->get($component->id);

            if (! $score || ! $score->isEntered()) {
                return null;
            }

            $sum += $score->is_absent ? 0 : (float) $score->score;
        }

        return round($sum, 2);
    }
}
