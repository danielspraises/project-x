<?php

namespace App\Notifications;

use App\Models\ResultSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResultReturnedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ResultSubmission $submission,
        public string $notes,
    ) {
    }

    /**
     * Notification delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Database notification payload.
     */
    public function toArray(object $notifiable): array
    {
        $course = $this->submission->courseOffering?->course;

        $courseLabel = $course?->code
            ? $course->code . ' — ' . $course->title
            : 'Result submission #' . $this->submission->id;

        return [
            'type' => 'result_returned',
            'title' => 'Results Returned for Correction',
            'message' => "Your {$courseLabel} results have been returned by the HOD.",
            'submission_id' => $this->submission->id,
            'course_offering_id' => $this->submission->course_offering_id,
            'course_code' => $course?->code,
            'course_title' => $course?->title,
            'notes' => $this->notes,
            'returned_at' => now()->toISOString(),
            'url' => url('/lecturer/results'),
        ];
    }
}