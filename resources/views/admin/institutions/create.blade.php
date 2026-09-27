<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Institution</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md text-red-800 text-sm">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.institutions.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 border-b pb-2">Institution details</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="block text-sm text-gray-600 mb-1">Institution name</label>
                                <input type="text" name="name" value="{{ old('name') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Education level</label>
                                <select name="education_level" id="education_level" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                    <option value="">Select level</option>
                                    <option value="tertiary" @selected(old('education_level') === 'tertiary')>Tertiary</option>
                                    <option value="secondary" @selected(old('education_level') === 'secondary')>Secondary (JSS/SSS)</option>
                                    <option value="primary" @selected(old('education_level') === 'primary')>Primary</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Institution category</label>
                                <select name="type" id="type" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                    <option value="">Select education level first</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Ownership</label>
                                <select name="ownership" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                    <option value="">Select ownership</option>
                                    <option value="federal" @selected(old('ownership') === 'federal')>Federal</option>
                                    <option value="state" @selected(old('ownership') === 'state')>State</option>
                                    <option value="private" @selected(old('ownership') === 'private')>Private</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 border-b pb-2">First ICT Admin login</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Full name</label>
                                <input type="text" name="ict_admin_name" value="{{ old('ict_admin_name') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Email</label>
                                <input type="email" name="ict_admin_email" value="{{ old('ict_admin_email') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">A temporary password will be generated automatically — you'll see it once, right after saving.</p>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 border-b pb-2">Billing (Super Admin controlled)</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Billing cycle</label>
                                <select name="billing_type" id="billing_type" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                    <option value="">Select cycle</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="annual">Annual</option>
                                    <option value="per_student">Per student</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Currency</label>
                                <input type="text" name="currency" value="{{ old('currency', 'NGN') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>

                            <div id="rate_amount_field">
                                <label class="block text-sm text-gray-600 mb-1">Rate amount (monthly/annual)</label>
                                <input type="number" step="0.01" name="rate_amount" value="{{ old('rate_amount') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div id="per_student_rate_field">
                                <label class="block text-sm text-gray-600 mb-1">Rate per student</label>
                                <input type="number" step="0.01" name="per_student_rate" value="{{ old('per_student_rate') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Billing anniversary date</label>
                                <input type="date" name="billing_anniversary_date" value="{{ old('billing_anniversary_date') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Contract start date</label>
                                <input type="date" name="contract_start_date" value="{{ old('contract_start_date') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Contract end date (optional)</label>
                                <input type="date" name="contract_end_date" value="{{ old('contract_end_date') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm text-gray-600 mb-1">Notes (deal-specific terms)</label>
                            <textarea name="notes" rows="2" class="w-full rounded-md border-gray-300 shadow-sm">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('admin.institutions.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                        <x-btn-primary type="submit" loading-text="Creating...">Create Institution</x-btn-primary>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Billing cycle: show only the relevant rate field.
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

        // Institution category options depend on the chosen education level.
        const categoryOptions = {
            tertiary: [
                { value: 'university', label: 'University' },
                { value: 'polytechnic', label: 'Polytechnic' },
                { value: 'college_of_education', label: 'College of Education' },
                { value: 'monotechnic', label: 'Monotechnic' },
            ],
            secondary: [
                { value: 'secondary_school', label: 'Secondary School' },
            ],
            primary: [
                { value: 'primary_school', label: 'Primary School' },
            ],
        };

        const educationLevel = document.getElementById('education_level');
        const typeSelect = document.getElementById('type');
        const oldType = '{{ old('type') }}';

        function populateCategoryOptions() {
            const options = categoryOptions[educationLevel.value] || [];
            typeSelect.innerHTML = '';

            if (options.length === 0) {
                typeSelect.innerHTML = '<option value="">Select education level first</option>';
                return;
            }

            typeSelect.innerHTML = '<option value="">Select category</option>';
            options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.label;
                if (opt.value === oldType) option.selected = true;
                typeSelect.appendChild(option);
            });

            // Secondary/primary only have one option — select it automatically.
            if (options.length === 1) {
                typeSelect.value = options[0].value;
            }
        }

        educationLevel.addEventListener('change', populateCategoryOptions);
        if (educationLevel.value) populateCategoryOptions();
    </script>
</x-app-layout>
