<?php

namespace App\Services\Learning;

use App\Models\LearningContent;
use App\Models\LearningContentAudit;
use App\Models\User;
use App\Notifications\LearningContentNotification;
use Illuminate\Support\Facades\DB;

class LearningContentGovernanceService
{
    public function record(
        LearningContent $content,
        User $user,
        string $action,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $remarks = null,
        ?array $metadata = null,
    ): LearningContentAudit {
        return LearningContentAudit::create([
            'institution_id' => $content->institution_id,
            'learning_content_id' => $content->id,
            'user_id' => $user->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'remarks' => $remarks,
            'metadata' => $metadata,
        ]);
    }

    public function transition(
        LearningContent $content,
        User $user,
        string $action,
        string $toStatus,
        ?string $remarks = null,
        ?array $metadata = null,
        array $additionalUpdates = [],
    ): LearningContentAudit {
        return DB::transaction(function () use (
            $content,
            $user,
            $action,
            $toStatus,
            $remarks,
            $metadata,
            $additionalUpdates
        ) {
            $fromStatus = $content->workflow_status;

            $content->update([
                'workflow_status' => $toStatus,
                ...$additionalUpdates,
            ]);

            $audit = $this->record(
                content: $content,
                user: $user,
                action: $action,
                fromStatus: $fromStatus,
                toStatus: $toStatus,
                remarks: $remarks,
                metadata: $metadata,
            );

            DB::afterCommit(function () use (
                $content,
                $user,
                $action,
                $remarks
            ) {
                $this->notifyForTransition(
                    content: $content,
                    actor: $user,
                    action: $action,
                    remarks: $remarks,
                );
            });

            return $audit;
        });
    }

    private function notifyForTransition(
        LearningContent $content,
        User $actor,
        string $action,
        ?string $remarks = null,
    ): void {
        if ($action === 'submitted') {
            $this->notifyReviewers($content, $actor);

            return;
        }

        if (in_array($action, [
            'returned',
            'approved',
            'published',
            'archived',
        ], true)) {
            $creator = $content->creator;

            if ($creator && $creator->id !== $actor->id) {
                $creator->notify(
                    new LearningContentNotification(
                        content: $content,
                        event: $action,
                        actorId: $actor->id,
                        remarks: $remarks,
                    )
                );
            }
        }
    }

    private function notifyReviewers(
        LearningContent $content,
        User $actor,
    ): void {
        $institution = $content->institution;

        if (! $institution) {
            return;
        }

        $permission = $content->content_type === 'lesson'
            ? 'lessons.review'
            : 'lectures.review';

        $reviewers = User::query()
            ->where('institution_id', $institution->id)
            ->where('id', '!=', $actor->id)
            ->whereHas('role', function ($query) use ($permission) {
                $query->whereHas('permissions', function ($query) use ($permission) {
                    $query->where('slug', $permission);
                });
            })
            ->get();

        foreach ($reviewers as $reviewer) {
            $reviewer->notify(
                new LearningContentNotification(
                    content: $content,
                    event: 'submitted',
                    actorId: $actor->id,
                )
            );
        }
    }
}
