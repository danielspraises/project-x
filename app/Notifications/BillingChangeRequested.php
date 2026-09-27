<?php

namespace App\Notifications;

use App\Models\BillingChangeRequest;
use Illuminate\Notifications\Notification;

class BillingChangeRequested extends Notification
{
    public function __construct(private BillingChangeRequest $billingRequest)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $institution = $this->billingRequest->institution;
        $requester = $this->billingRequest->requestedBy;

        return [
            'title' => 'Billing change requested',
            'body' => "{$requester?->name} at {$institution?->name} requested to switch to "
                . ucfirst(str_replace('_', ' ', $this->billingRequest->requested_value)) . ' billing.',
            'billing_request_id' => $this->billingRequest->id,
            'institution_id' => $institution?->id,
            // Where clicking the notification should land — the institution's management
            // page already shows this exact pending request with Approve/Reject buttons.
            'target_url' => route('admin.institutions.edit', $institution?->id),
        ];
    }
}
