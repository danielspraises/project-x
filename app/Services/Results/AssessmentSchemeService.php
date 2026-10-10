<?php

namespace App\Services\Results;

use App\Models\AssessmentComponent;
use App\Models\AssessmentScheme;
use App\Models\CourseOffering;
use App\Models\Institution;
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
            return $this->syncWithInstitution($scheme);
        }

        // CourseOffering/SubjectOffering define no institution() relation, so
        // load the institution by id rather than through a relation.
        $settings = Institution::findOrFail($offering->institution_id)->assessmentSettings();

        return DB::transaction(function () use ($offering, $column, $settings, $createdBy) {
            $scheme = AssessmentScheme::create([
                'institution_id' => $offering->institution_id,
                $column => $offering->id,
                'ca_max' => (int) $settings['ca_weight'],
                'exam_max' => (int) $settings['exam_weight'],
                'status' => 'draft',
                'created_by' => $createdBy?->id,
            ]);

            if ($scheme->ca_max > 0) {
                AssessmentComponent::create([
                    'institution_id' => $offering->institution_id,
                    'assessment_scheme_id' => $scheme->id,
                    'type' => AssessmentComponent::TYPE_CA,
                    'name' => 'CA',
                    'max_score' => $scheme->ca_max,
                    'order' => 0,
                ]);
            }

            if ($scheme->exam_max > 0) {
                AssessmentComponent::create([
                    'institution_id' => $offering->institution_id,
                    'assessment_scheme_id' => $scheme->id,
                    'type' => AssessmentComponent::TYPE_EXAM,
                    'name' => 'Exam',
                    'max_score' => $scheme->exam_max,
                    'order' => 1,
                ]);
            }

            return $scheme->load('components');
        });
    }

    /**
     * Keep an unscored scheme in step with the institution's current
     * CA/Exam weights (set by the ICT Admin). A locked scheme already has
     * scores under it, so it keeps its own split — changing weights after
     * marks exist would silently change those marks.
     *
     * A single CA component simply resizes. If the teacher split the CA into
     * several, their split is kept but no longer adds up, so isConsistent()
     * turns false and the teacher is asked to re-split it.
     */
    public function syncWithInstitution(AssessmentScheme $scheme): AssessmentScheme
    {
        if ($scheme->isLocked()) {
            return $scheme;
        }

        $settings = Institution::findOrFail($scheme->institution_id)->assessmentSettings();
        $caMax = (int) $settings['ca_weight'];
        $examMax = (int) $settings['exam_weight'];

        if ((int) $scheme->ca_max === $caMax && (int) $scheme->exam_max === $examMax) {
            return $scheme;
        }

        return DB::transaction(function () use ($scheme, $caMax, $examMax) {
            $scheme->update(['ca_max' => $caMax, 'exam_max' => $examMax]);

            $components = $scheme->components()->get();
            $exam = $components->firstWhere('type', AssessmentComponent::TYPE_EXAM);
            $nonExam = $components->where('type', '!=', AssessmentComponent::TYPE_EXAM)->values();

            if ($examMax === 0) {
                $exam?->delete();
            } elseif ($exam) {
                $exam->update(['max_score' => $examMax]);
            } else {
                AssessmentComponent::create([
                    'institution_id' => $scheme->institution_id,
                    'assessment_scheme_id' => $scheme->id,
                    'type' => AssessmentComponent::TYPE_EXAM,
                    'name' => 'Exam',
                    'max_score' => $examMax,
                    'order' => 999,
                ]);
            }

            if ($caMax === 0) {
                $nonExam->each->delete();
            } elseif ($nonExam->isEmpty()) {
                AssessmentComponent::create([
                    'institution_id' => $scheme->institution_id,
                    'assessment_scheme_id' => $scheme->id,
                    'type' => AssessmentComponent::TYPE_CA,
                    'name' => 'CA',
                    'max_score' => $caMax,
                    'order' => 0,
                ]);
            } elseif ($nonExam->count() === 1) {
                $nonExam->first()->update(['max_score' => $caMax]);
            }

            return $scheme->fresh('components');
        });
    }

    /**
     * True when the components add up to exactly the scheme's CA and exam
     * shares. Entry is blocked while this is false, so a total can never be
     * computed from a split that doesn't match the institution's weights.
     */
    public function isConsistent(AssessmentScheme $scheme): bool
    {
        $scheme->loadMissing('components');

        $exam = $scheme->components->where('type', AssessmentComponent::TYPE_EXAM);
        $nonExam = $scheme->components->where('type', '!=', AssessmentComponent::TYPE_EXAM);

        $examOk = (int) $scheme->exam_max > 0
            ? $exam->count() === 1 && (int) $exam->first()->max_score === (int) $scheme->exam_max
            : $exam->isEmpty();

        $caOk = (int) $scheme->ca_max > 0
            ? $nonExam->isNotEmpty() && (int) $nonExam->sum('max_score') === (int) $scheme->ca_max
            : $nonExam->isEmpty();

        return $examOk && $caOk;
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

        if ((int) $scheme->exam_max > 0) {
            if (count($examComponents) !== 1 || (int) $examComponents[0]['max_score'] !== (int) $scheme->exam_max) {
                throw ValidationException::withMessages([
                    'components' => "A scheme needs exactly one exam component worth {$scheme->exam_max} marks.",
                ]);
            }
        } elseif (count($examComponents) > 0) {
            throw ValidationException::withMessages([
                'components' => 'This institution awards no marks to the exam, so there is no exam component.',
            ]);
        }

        $nonExamTotal = array_sum(array_map(fn ($c) => (int) $c['max_score'], $nonExamComponents));

        if ((int) $scheme->ca_max > 0) {
            if (empty($nonExamComponents)) {
                throw ValidationException::withMessages([
                    'components' => 'At least one CA, quiz, assignment, or attendance component is required.',
                ]);
            }

            if ($nonExamTotal !== (int) $scheme->ca_max) {
                throw ValidationException::withMessages([
                    'components' => "The non-exam components must add up to exactly {$scheme->ca_max} marks (currently {$nonExamTotal}).",
                ]);
            }
        } elseif (! empty($nonExamComponents)) {
            throw ValidationException::withMessages([
                'components' => 'This institution awards no marks to continuous assessment, so there are no CA components.',
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
