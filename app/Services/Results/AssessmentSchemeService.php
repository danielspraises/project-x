<?php

namespace App\Services\Results;

use App\Models\AssessmentComponent;
use App\Models\AssessmentScheme;
use App\Models\CourseOffering;
use App\Models\ResultAuditLog;
use App\Models\SubjectOffering;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentSchemeService
{
    /**
     * Get this offering's scheme, creating a default one (single CA + single
     * Exam component, split per the institution's ca_weight/exam_weight) if
     * it doesn't exist yet. Existing offerings created before this feature
     * get their scheme lazily, the first time anyone opens the entry page —
     * no backfill migration needed.
     */
    public function getOrCreateForOffering(CourseOffering|SubjectOffering $offering, ?User $createdBy = null): AssessmentScheme
    {
        $column = $offering instanceof CourseOffering ? 'course_offering_id' : 'subject_offering_id';

        $scheme = AssessmentScheme::where($column, $offering->id)->first();

        if ($scheme) {
            return $scheme;
        }

        $settings = $offering->institution->assessmentSettings();

        return DB::transaction(function () use ($offering, $column, $settings, $createdBy) {
            $scheme = AssessmentScheme::create([
                'institution_id' => $offering->institution_id,
                $column => $offering->id,
                'ca_max' => (int) $settings['ca_weight'],
                'exam_max' => (int) $settings['exam_weight'],
                'status' => 'draft',
                'created_by' => $createdBy?->id,
            ]);

            AssessmentComponent::create([
                'institution_id' => $offering->institution_id,
                'assessment_scheme_id' => $scheme->id,
                'type' => AssessmentComponent::TYPE_CA,
                'name' => 'CA',
                'max_score' => $scheme->ca_max,
                'order' => 0,
            ]);

            AssessmentComponent::create([
                'institution_id' => $offering->institution_id,
                'assessment_scheme_id' => $scheme->id,
                'type' => AssessmentComponent::TYPE_EXAM,
                'name' => 'Exam',
                'max_score' => $scheme->exam_max,
                'order' => 1,
            ]);

            return $scheme->load('components');
        });
    }

    /**
     * Replace a scheme's components with a teacher-specified split — e.g.
     * three CAs instead of one. Validates before writing anything:
     * - exactly one 'exam' component, whose max equals the scheme's fixed exam_max
     * - every other component's max sums to exactly the scheme's fixed ca_max
     * Refuses entirely once the scheme is locked.
     *
     * @param array<int, array{type: string, name: string, max_score: int, order?: int}> $components
     */
    public function replaceComponents(AssessmentScheme $scheme, array $components, ?User $actor = null): AssessmentScheme
    {
        if ($scheme->isLocked()) {
            throw ValidationException::withMessages([
                'scheme' => 'This assessment scheme is locked because scores already exist. Unlock it first to change the component split.',
            ]);
        }

        $examComponents = array_values(array_filter($components, fn ($c) => $c['type'] === AssessmentComponent::TYPE_EXAM));
        $nonExamComponents = array_values(array_filter($components, fn ($c) => $c['type'] !== AssessmentComponent::TYPE_EXAM));

        if (count($examComponents) !== 1) {
            throw ValidationException::withMessages([
                'components' => 'A scheme needs exactly one exam component.',
            ]);
        }

        if ((int) $examComponents[0]['max_score'] !== (int) $scheme->exam_max) {
            throw ValidationException::withMessages([
                'components' => "The exam component must be worth exactly {$scheme->exam_max} marks.",
            ]);
        }

        if (empty($nonExamComponents)) {
            throw ValidationException::withMessages([
                'components' => 'At least one CA, quiz, assignment, or attendance component is required.',
            ]);
        }

        $nonExamTotal = array_sum(array_map(fn ($c) => (int) $c['max_score'], $nonExamComponents));

        if ($nonExamTotal !== (int) $scheme->ca_max) {
            throw ValidationException::withMessages([
                'components' => "The non-exam components must add up to exactly {$scheme->ca_max} marks (currently {$nonExamTotal}).",
            ]);
        }

        DB::transaction(function () use ($scheme, $components) {
            $scheme->components()->delete();

            foreach (array_values($components) as $index => $component) {
                AssessmentComponent::create([
                    'institution_id' => $scheme->institution_id,
                    'assessment_scheme_id' => $scheme->id,
                    'type' => $component['type'],
                    'name' => $component['name'],
                    'max_score' => $component['max_score'],
                    'order' => $component['order'] ?? $index,
                ]);
            }
        });

        return $scheme->fresh('components');
    }

    public function lock(AssessmentScheme $scheme): void
    {
        if ($scheme->isLocked()) {
            return;
        }

        $scheme->update(['status' => 'locked', 'locked_at' => now()]);
    }

    /**
     * Unlocking is a controlled, audited action — it's meant for correcting
     * a scheme mistake, not routine editing, since changing a max after
     * scores exist can silently invalidate totals already entered.
     */
    public function unlock(AssessmentScheme $scheme, User $actor, string $reason): void
    {
        $scheme->update(['status' => 'draft', 'locked_at' => null]);

        ResultAuditLog::create([
            'institution_id' => $scheme->institution_id,
            'student_result_id' => null,
            'result_submission_id' => null,
            'user_id' => $actor->id,
            'action' => 'assessment_scheme_unlocked',
            'reason' => $reason,
            'old_values' => ['status' => 'locked'],
            'new_values' => ['status' => 'draft'],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
