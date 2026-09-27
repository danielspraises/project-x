<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\BillingChangeRequest;
use App\Models\User;
use App\Notifications\BillingChangeRequested;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function show(): View
    {
        abort_unless(Auth::user()->hasPermission('billing.view'), 403);

        $institution = Auth::user()->institution;
        $billingConfig = $institution->billingConfig;

        $pendingRequests = BillingChangeRequest::where('institution_id', $institution->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $pastRequests = BillingChangeRequest::where('institution_id', $institution->id)
            ->where('status', '!=', 'pending')
            ->latest()
            ->limit(5)
            ->get();

        return view('ict-admin.billing.show', compact('institution', 'billingConfig', 'pendingRequests', 'pastRequests'));
    }

    /**
     * ICT Admin can never edit billing directly — only request a change. An Administrator
     * has to review and approve it before anything on the actual billing record moves.
     */
    public function requestChange(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->hasPermission('billing.request_change'), 403);

        $validated = $request->validate([
            'billing_cycle' => ['required', 'in:monthly,annual,per_student'],
            'effective_date' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $billingRequest = BillingChangeRequest::create([
            'institution_id' => Auth::user()->institution_id,
            'requested_by' => Auth::id(),
            'field_requested' => 'billing_type',
            'requested_value' => $validated['billing_cycle'],
            'effective_date' => $validated['effective_date'],
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        $superAdmins = User::whereNull('institution_id')
            ->whereHas('role', fn ($q) => $q->where('slug', 'super_admin'))
            ->get();

        foreach ($superAdmins as $superAdmin) {
            $superAdmin->notify(new BillingChangeRequested($billingRequest));
        }

        return redirect()->route('ict-admin.billing.show')->with('success', 'Change request submitted — an Administrator will review it.');
    }
}
