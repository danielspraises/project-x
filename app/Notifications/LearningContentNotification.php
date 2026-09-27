<?php

namespace App\Notifications;

use App\Models\LearningContent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LearningContentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LearningContent $content,
        public string $event,
        public int $actorId,
        public ?string $remarks = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $contentType = $this->content->content_type === 'lesson'
            ? 'Lesson'
            : 'Lecture';

        $messages = [
            'submitted' => [
                'title' => "{$contentType} Awaiting Review",
                'message' => "A {$contentType} titled \"{$this->content->title}\" has been submitted for review.",
            ],
            'returned' => [
                'title' => "{$contentType} Returned for Revision",
                'message' => "Your {$contentType} titled \"{$this->content->title}\" has been returned for revision.",
            ],
            'approved' => [
                'title' => "{$contentType} Approved",
                'message' => "Your {$contentType} titled \"{$this->content->title}\" has been approved.",
            ],
            'published' => [
                'title' => "{$contentType} Published",
                'message' => "Your {$contentType} titled \"{$this->content->title}\" has been published.",
            ],
            'archived' => [
                'title' => "{$contentType} Archived",
                'message' => "Your {$contentType} titled \"{$this->content->title}\" has been archived.",
            ],
        ];

        $notification = $messages[$this->event] ?? [
            'title' => "{$contentType} Updated",
            'message' => "Your {$contentType} titled \"{$this->content->title}\" has been updated.",
        ];

        return [
            'type' => 'learning_content_' . $this->event,
            'title' => $notification['title'],
            'message' => $notification['message'],

            'learning_content_id' => $this->content->id,
            'content_type' => $this->content->content_type,
            'title_text' => $this->content->title,

            'subject_offering_id' => $this->content->subject_offering_id,
            'course_offering_id' => $this->content->course_offering_id,

            'workflow_status' => $this->content->workflow_status,
            'remarks' => $this->remarks,

            'actor_id' => $this->actorId,
            'occurred_at' => now()->toISOString(),

            'url' => url('/learning'),
        ];
    }
}
