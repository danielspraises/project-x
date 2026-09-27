<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Billing</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-2 border-b pb-2">Current plan</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Billing terms are set by your platform Administrator. You can view your current status here and request a change below — nothing here can be edited directly.
                </p>

                @if ($billingConfig)
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Billing cycle</dt>
                            <dd class="font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $billingConfig->billing_type)) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Currency</dt>
                            <dd class="font-medium text-gray-900">{{ $billingConfig->currency }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Rate</dt>
                            <dd class="font-medium text-gray-900">
                                {{ $billingConfig->currency }}
                                {{ number_format($billingConfig->billing_type === 'per_student' ? $billingConfig->per_student_rate : $billingConfig->rate_amount, 2) }}
                                {{ $billingConfig->billing_type === 'per_student' ? 'per student' : '' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Billing anniversary</dt>
                            <dd class="font-medium text-gray-900">{{ $billingConfig->billing_anniversary_date->format('d M Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Status</dt>
                            <dd>
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    @class([
                                        'bg-yellow-100 text-yellow-800' => $billingConfig->status === 'trial',
                                        'bg-green-100 text-green-800' => $billingConfig->status === 'active',
                                        'bg-red-100 text-red-800' => in_array($billingConfig->status, ['suspended', 'cancelled']),
                                    ])">
                                    {{ ucfirst($billingConfig->status) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Contract start</dt>
                            <dd class="font-medium text-gray-900">{{ $billingConfig->contract_start_date->format('d M Y') }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="text-sm text-gray-500">No billing configuration set up yet — contact your Administrator.</p>
                @endif
            </div>

            @if ($pendingRequests->isNotEmpty())
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 space-y-3">
                    <p class="text-sm font-medium text-amber-900">Pending request{{ $pendingRequests->count() > 1 ? 's' : '' }}</p>
                    @foreach ($pendingRequests as $req)
                        <div class="text-xs text-amber-800 bg-white rounded-md p-3">
                            <p>Switch to <strong>{{ ucfirst(str_replace('_', ' ', $req->requested_value)) }}</strong> billing, effective {{ $req->effective_date?->format('d M Y') }}</p>
                            @if ($req->reason)
                                <p class="mt-1 text-amber-700">"{{ $req->reason }}"</p>
                            @endif
                            <p class="mt-1 text-amber-600">Submitted {{ $req->created_at->diffForHumans() }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4 border-b pb-2">Request a change</h3>

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md text-red-800 text-sm">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('ict-admin.billing.request-change') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Billing cycle</label>
                        <select name="billing_cycle" class="w-full rounded-md border-gray-300 shadow-sm" required>
                            <option value="">Select a cycle</option>
                            <option value="monthly" @selected(old('billing_cycle') === 'monthly')>Monthly</option>
                            <option value="annual" @selected(old('billing_cycle') === 'annual')>Annual</option>
                            <option value="per_student" @selected(old('billing_cycle') === 'per_student')>Per student</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Change commencement date</label>
                        <input type="date" name="effective_date" value="{{ old('effective_date') }}"
                               min="{{ now()->format('Y-m-d') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                        <p class="text-xs text-gray-500 mt-1">When you'd like this change to take effect, if approved.</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Reason / note (optional)</label>
                        <textarea name="reason" rows="3" placeholder="Let your Administrator know why you're requesting this change..."
                                  class="w-full rounded-md border-gray-300 shadow-sm">{{ old('reason') }}</textarea>
                    </div>
                    <div class="flex justify-end">
                        <x-btn-primary type="submit" loading-text="Submitting...">Submit Request</x-btn-primary>
                    </div>
                </form>
            </div>

            @if ($pastRequests->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4 border-b pb-2">Past requests</h3>
                    <div class="space-y-3">
                        @foreach ($pastRequests as $req)
                            <div class="text-sm">
                                <div class="flex justify-between items-start">
                                    <span class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $req->requested_value)) }} billing, effective {{ $req->effective_date?->format('d M Y') }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium shrink-0 ml-2
                                        @class([
                                            'bg-green-100 text-green-800' => $req->status === 'approved',
                                            'bg-red-100 text-red-800' => $req->status === 'rejected',
                                        ])">
                                        {{ ucfirst($req->status) }}
                                    </span>
                                </div>
                                @if ($req->reason)
                                    <p class="text-xs text-gray-400 mt-0.5">"{{ $req->reason }}"</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
