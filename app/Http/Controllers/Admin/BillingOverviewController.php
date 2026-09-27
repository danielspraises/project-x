<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingChangeRequest;
use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BillingOverviewController extends Controller
{
    /**
     * One place to see every institution's billing status and every pending request
     * across the whole platform — instead of opening each institution individually.
     */
    public function index(): View
    {
        $institutions = Institution::with('billingConfig')
            ->withCount(['billingChangeRequests as pending_requests_count' => function ($query) {
                $query->where('status', 'pending');
            }])
            ->orderBy('name')
            ->get();

        $pendingRequests = BillingChangeRequest::with(['institution', 'requestedBy'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('admin.billing.index', compact('institutions', 'pendingRequests'));
    }

    public function approve(BillingChangeRequest $billingRequest): RedirectResponse
    {
        abort_unless($billingRequest->status === 'pending', 422, 'This request has already been resolved.');
        abort_unless(
            in_array($billingRequest->requested_value, ['monthly', 'annual', 'per_student']),
            422,
            'Invalid billing cycle requested.'
        );

        $billingRequest->institution->billingConfig->update([
            'billing_type' => $billingRequest->requested_value,
            'billing_anniversary_date' => $billingRequest->effective_date ?? $billingRequest->institution->billingConfig->billing_anniversary_date,
        ]);

        $billingRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.billing.index')->with('success', 'Billing change approved and applied.');
    }

    public function reject(BillingChangeRequest $billingRequest): RedirectResponse
    {
        abort_unless($billingRequest->status === 'pending', 422, 'This request has already been resolved.');

        $billingRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.billing.index')->with('success', 'Billing change rejected.');
    }
}
