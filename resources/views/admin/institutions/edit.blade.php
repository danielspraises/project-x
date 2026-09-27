<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight truncate">Manage: {{ $institution->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('admin.institutions.index') }}" class="text-sm text-gray-600 hover:text-gray-900 inline-block">&larr; Back to Institutions</a>

            @if (session('success'))
                <div class="p-4 bg-green-50 border border-green-200 rounded-md text-green-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('generated_password'))
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-md text-blue-900 text-sm">
                    <strong>New password for {{ session('reset_password_for') }}:</strong>
                    <code class="bg-white px-2 py-1 rounded border border-blue-200">{{ session('generated_password') }}</code>
                    <p class="mt-1 text-blue-700">Share this securely — it won't be shown again.</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-red-50 border border-red-200 rounded-md text-red-800 text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($pendingBillingRequests->isNotEmpty())
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <p class="text-sm font-medium text-amber-900 mb-3">Pending billing change requests</p>
                    <div class="space-y-2">
                        @foreach ($pendingBillingRequests as $req)
                            <div class="flex items-center justify-between bg-white rounded-md p-3 text-sm">
                                <div>
                                    <p class="text-gray-900">Switch to <strong>{{ ucfirst(str_replace('_', ' ', $req->requested_value)) }}</strong> billing, effective <strong>{{ $req->effective_date?->format('d M Y') }}</strong></p>
                                    @if ($req->reason)
                                        <p class="text-xs text-gray-600 mt-1 italic">"{{ $req->reason }}"</p>
                                    @endif
                                    <p class="text-xs text-gray-500 mt-1">Requested by {{ $req->requestedBy->name ?? 'Unknown' }} — {{ $req->created_at->diffForHumans() }}</p>
                                </div>
                                <div class="flex gap-2 shrink-0 ml-4">
                                    <form method="POST" action="{{ route('admin.institutions.billing-requests.approve', [$institution, $req]) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.institutions.billing-requests.reject', [$institution, $req]) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded-md text-xs font-medium hover:bg-gray-300">Reject</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.institutions.update', $institution) }}" class="space-y-6">
                @csrf @method('PUT')

                <!-- Institution details -->
                <div class="ui-panel p-6 sm:p-7">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4 border-b pb-2">Institution details</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm text-gray-600 mb-1">Institution name</label>
                            <input type="text" name="name" value="{{ old('name', $institution->name) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Education level</label>
                            <input type="text" value="{{ ucfirst($institution->education_level) }}" disabled
                                   class="w-full rounded-md border-gray-200 bg-gray-50 text-gray-500 shadow-sm">
                            <p class="text-xs text-gray-500 mt-1">Locked after creation — changing this would orphan existing structure.</p>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Institution category</label>
                            <select name="type" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                @if ($institution->education_level === 'tertiary')
                                    <option value="university" @selected(old('type', $institution->type) === 'university')>University</option>
                                    <option value="polytechnic" @selected(old('type', $institution->type) === 'polytechnic')>Polytechnic</option>
                                    <option value="college_of_education" @selected(old('type', $institution->type) === 'college_of_education')>College of Education</option>
                                    <option value="monotechnic" @selected(old('type', $institution->type) === 'monotechnic')>Monotechnic</option>
                                @elseif ($institution->education_level === 'secondary')
                                    <option value="secondary_school" selected>Secondary School</option>
                                @else
                                    <option value="primary_school" selected>Primary School</option>
                                @endif
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Ownership</label>
                            <select name="ownership" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="federal" @selected(old('ownership', $institution->ownership) === 'federal')>Federal</option>
                                <option value="state" @selected(old('ownership', $institution->ownership) === 'state')>State</option>
                                <option value="private" @selected(old('ownership', $institution->ownership) === 'private')>Private</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Status</label>
                            <select name="status" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                @foreach (['trial', 'active', 'suspended', 'cancelled'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', $institution->status) === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Suspending blocks the institution's write access platform-wide.</p>
                        </div>
                    </div>
                </div>

                <!-- Branding -->
                <div class="ui-panel ui-theme-editor p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="ui-eyebrow">Institution theme defaults</div>
                            <h3 class="mt-2 ui-heading text-xl">Light &amp; dark appearance</h3>
                            <p class="mt-2 ui-muted max-w-2xl text-sm leading-6">
                                These are the Super Admin fallback colours for this institution. An ICT Admin can customise the same four colours from Institution Settings.
                            </p>
                        </div>
                        <span class="ui-badge">Fallback</span>
                    </div>

                    <div class="mt-7 grid gap-5 md:grid-cols-2">
                        @foreach([
                            ['light_primary','Light primary','Main accent, active navigation and primary actions.'],
                            ['light_secondary','Light secondary','Secondary accent used for gradients and supporting emphasis.'],
                            ['dark_primary','Dark primary','Main accent used while the application is in dark mode.'],
                            ['dark_secondary','Dark secondary','Secondary dark-mode accent used for gradients and glow.'],
                        ] as [$key,$label,$help])
                            <div class="ui-color-field">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <label class="ui-label" for="{{ $key }}">{{ $label }}</label>
                                        <p class="ui-muted mt-1 text-xs">{{ $help }}</p>
                                    </div>
                                    <input id="{{ $key }}" type="color" name="{{ $key }}"
                                           value="{{ old($key, $branding[$key] ?? $institution->themeDefaults()[$key]) }}"
                                           class="ui-color-picker" aria-label="{{ $label }}">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 ui-note">
                        <strong>Legacy branding:</strong> Primary and secondary values remain supported for compatibility. New screens use the light/dark values above.
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="ui-label" for="primary_color">Legacy primary</label>
                            <input id="primary_color" type="color" name="primary_color"
                                   value="{{ old('primary_color', $branding['primary_color'] ?? '#5b5ff5') }}"
                                   class="ui-color-picker mt-2">
                        </div>
                        <div>
                            <label class="ui-label" for="secondary_color">Legacy secondary</label>
                            <input id="secondary_color" type="color" name="secondary_color"
                                   value="{{ old('secondary_color', $branding['secondary_color'] ?? '#7c3aed') }}"
                                   class="ui-color-picker mt-2">
                        </div>
                    </div>
                </div>

                <!-- Billing -->
                <div class="ui-panel p-6 sm:p-7">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4 border-b pb-2">Billing (Administrator controlled)</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Billing cycle</label>
                            <select name="billing_type" id="billing_type" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="monthly" @selected(old('billing_type', $institution->billingConfig->billing_type ?? '') === 'monthly')>Monthly</option>
                                <option value="annual" @selected(old('billing_type', $institution->billingConfig->billing_type ?? '') === 'annual')>Annual</option>
                                <option value="per_student" @selected(old('billing_type', $institution->billingConfig->billing_type ?? '') === 'per_student')>Per student</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Currency</label>
                            <input type="text" name="currency" value="{{ old('currency', $institution->billingConfig->currency ?? 'NGN') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div id="rate_amount_field">
                            <label class="block text-sm text-gray-600 mb-1">Rate amount</label>
                            <input type="number" step="0.01" name="rate_amount" value="{{ old('rate_amount', $institution->billingConfig->rate_amount ?? '') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div id="per_student_rate_field">
                            <label class="block text-sm text-gray-600 mb-1">Rate per student</label>
                            <input type="number" step="0.01" name="per_student_rate" value="{{ old('per_student_rate', $institution->billingConfig->per_student_rate ?? '') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Billing anniversary date</label>
                            <input type="date" name="billing_anniversary_date"
                                   value="{{ old('billing_anniversary_date', optional($institution->billingConfig->billing_anniversary_date ?? null)->format('Y-m-d')) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Contract start date</label>
                            <input type="date" name="contract_start_date"
                                   value="{{ old('contract_start_date', optional($institution->billingConfig->contract_start_date ?? null)->format('Y-m-d')) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Contract end date</label>
                            <input type="date" name="contract_end_date"
                                   value="{{ old('contract_end_date', optional($institution->billingConfig->contract_end_date ?? null)->format('Y-m-d')) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Billing status</label>
                            <select name="billing_status" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                @foreach (['trial', 'active', 'suspended', 'cancelled'] as $status)
                                    <option value="{{ $status }}" @selected(old('billing_status', $institution->billingConfig->status ?? '') === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm text-gray-600 mb-1">Notes</label>
                        <textarea name="notes" rows="2" class="w-full rounded-md border-gray-300 shadow-sm">{{ old('notes', $institution->billingConfig->notes ?? '') }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-btn-primary type="submit" loading-text="Saving...">Save Changes</x-btn-primary>
                </div>
            </form>

            <!-- Feature Access -->
            <div class="ui-panel p-6 sm:p-7">
                <h3 class="text-sm font-semibold text-gray-700 mb-2 border-b pb-2">Feature Access</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Control which modules this institution can use. Unchecking a feature blocks it immediately — both the sidebar link and the underlying pages.
                </p>
                <form method="POST" action="{{ route('admin.institutions.features.update', $institution) }}" class="space-y-4">
                    @csrf @method('PUT')

                    @php($groupedFeatures = $features->groupBy('category'))
                    @foreach ($groupedFeatures as $category => $categoryFeatures)
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">{{ $category ?? 'Other' }}</p>
                            <div class="space-y-2">
                                @foreach ($categoryFeatures as $feature)
                                    <label class="flex items-start gap-3 p-3 rounded-md border border-gray-100 hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" name="feature_ids[]" value="{{ $feature->id }}"
                                               class="mt-0.5 rounded border-gray-300"
                                               @checked(in_array($feature->id, $enabledFeatureIds))>
                                        <span class="min-w-0">
                                            <span class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-gray-800">{{ $feature->name }}</span>
                                                @if ($feature->is_core)
                                                    <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded text-[10px] font-medium">Core</span>
                                                @endif
                                            </span>
                                            @if ($feature->description)
                                                <span class="block text-xs text-gray-500 mt-0.5">{{ $feature->description }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="flex justify-end pt-2">
                        <x-btn-primary type="submit" loading-text="Saving...">Save Feature Access</x-btn-primary>
                    </div>
                </form>
            </div>

            <!-- Add ICT Admin -->
            <div class="ui-panel p-6 sm:p-7">
                <h3 class="text-sm font-semibold text-gray-700 mb-2 border-b pb-2">Add an ICT Admin</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Only you can do this — no ICT Admin, at any institution, can create or reassign this role themselves. Use this to add a replacement or backup.
                </p>
                <form method="POST" action="{{ route('admin.institutions.ict-admins.store', $institution) }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-sm text-gray-600 mb-1">Full name</label>
                        <input type="text" name="name" class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-sm text-gray-600 mb-1">Email</label>
                        <input type="email" name="email" class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <x-btn-primary type="submit" loading-text="Adding...">Add ICT Admin</x-btn-primary>
                </form>
            </div>

            <!-- Users — password reset -->
            <div class="ui-panel p-6 sm:p-7">
                <h3 class="text-sm font-semibold text-gray-700 mb-4 border-b pb-2">Users</h3>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase pb-2">Name</th>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase pb-2">Email</th>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase pb-2">Role</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($institution->users as $user)
                            @php($confirmMsg = 'This permanently deletes "' . $user->name . '"\'s account. Only the platform Administrator can do this. This cannot be undone.')
                            <tr>
                                <td class="py-3 text-sm text-gray-900">{{ $user->name }}{{ $user->is_primary ? ' (Primary)' : '' }}</td>
                                <td class="py-3 text-sm text-gray-500">{{ $user->email }}</td>
                                <td class="py-3 text-sm text-gray-500">{{ $user->role->name ?? '—' }}</td>
                                <td class="py-3 text-sm text-right space-x-3">
                                    <form method="POST" action="{{ route('admin.institutions.reset-password', [$institution, $user]) }}"
                                          class="inline"
                                          onsubmit="return confirm('Reset password for {{ $user->name }}? A new one will be generated.');">
                                        @csrf
                                        <button type="submit" class="text-gray-600 hover:text-gray-900 text-sm">Reset Password</button>
                                    </form>
                                    <span class="inline-block">
                                        <x-delete-button
                                            :action="route('admin.institutions.users.destroy', [$institution, $user])"
                                            label="Delete Account"
                                            confirm-title="Delete this account?"
                                            :confirm-message="$confirmMsg"
                                        />
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-sm text-gray-500 text-center">No users yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="ui-panel p-6 sm:p-7 border-2 border-red-100">
                <h3 class="text-sm font-semibold text-red-700 mb-2">Danger zone</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Permanently deletes this institution and everything tied to it — every user, faculty, department, student, and billing record. This cannot be undone and cannot be recovered.
                </p>
                @php($deleteInstitutionMsg = 'This permanently deletes "' . $institution->name . '" and ALL of its data — users, students, faculties, everything. This absolutely cannot be undone.')
                <x-delete-button
                    :action="route('admin.institutions.destroy', $institution)"
                    label="Delete Institution Permanently"
                    confirm-title="Delete this entire institution?"
                    :confirm-message="$deleteInstitutionMsg"
                />
            </div>

        </div>
    </div>

    <script>
        const billingType = document.getElementById('billing_type');
        const rateField = document.getElementById('rate_amount_field');
        const perStudentField = document.getElementById('per_student_rate_field');

        function toggleRateFields() {
            if (billingType.value === 'per_student') {
                rateField.style.display = 'none';
                perStudentField.style.display = 'block';
            } else {
                rateField.style.display = 'block';
                perStudentField.style.display = 'none';
            }
        }
        billingType.addEventListener('change', toggleRateFields);
        toggleRateFields();
    </script>
</x-app-layout>
