<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Student</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
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

                <form method="POST" action="{{ route('ict-admin.students.update', $student) }}" class="space-y-6">
                    @csrf @method('PUT')

                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 border-b pb-2">Identification</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Admission number</label>
                                <input type="text" name="admission_number" value="{{ old('admission_number', $student->admission_number) }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm" required>
                                @error('admission_number')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            @if ($institution->education_level === 'tertiary')
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Matric number</label>
                                    <input type="text" name="matric_number" value="{{ old('matric_number', $student->matric_number) }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                            @endif
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 border-b pb-2">Personal details</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">First name</label>
                                <input type="text" name="first_name" value="{{ old('first_name', $student->first_name) }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Last name</label>
                                <input type="text" name="last_name" value="{{ old('last_name', $student->last_name) }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Other names</label>
                                <input type="text" name="other_names" value="{{ old('other_names', $student->other_names) }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Gender</label>
                                <select name="gender" class="w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">Select</option>
                                    <option value="male" @selected(old('gender', $student->gender) === 'male')>Male</option>
                                    <option value="female" @selected(old('gender', $student->gender) === 'female')>Female</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Date of birth</label>
                                <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone', $student->phone) }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Email</label>
                                <input type="email" name="email" value="{{ old('email', $student->email) }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Status</label>
                                <select name="status" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                    @foreach (['active', 'graduated', 'withdrawn', 'suspended'] as $status)
                                        <option value="{{ $status }}" @selected(old('status', $student->status) === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm text-gray-600 mb-1">Address</label>
                            <textarea name="address" rows="2" class="w-full rounded-md border-gray-300 shadow-sm">{{ old('address', $student->address) }}</textarea>
                        </div>
                    </div>

                    @if ($institution->education_level === 'tertiary')
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3 border-b pb-2">Academic placement</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Department</label>
                                    <select name="department_id" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}" @selected(old('department_id', $student->department_id) == $department->id)>{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Programme</label>
                                    <select name="programme_id" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                        @foreach ($programmes as $programme)
                                            <option value="{{ $programme->id }}" @selected(old('programme_id', $student->programme_id) == $programme->id)>{{ $programme->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Level</label>
                                    <input type="text" name="level" value="{{ old('level', $student->level) }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>
                            </div>
                        </div>
                    @else
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3 border-b pb-2">Academic placement</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Class</label>
                                    <select name="class_id" id="class_id" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                        @foreach ($classes as $class)
                                            <option value="{{ $class->id }}" @selected(old('class_id', $student->class_id) == $class->id)>{{ $class->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Arm</label>
                                    <select name="arm_id" id="arm_id" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('ict-admin.students.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                        <x-btn-primary type="submit" loading-text="Updating...">Update Student</x-btn-primary>
                    </div>
                </form>
            </div>

            <div class="mt-6">
                <x-notes-panel :notable="$student" :store-route="route('ict-admin.notes.store', ['type' => 'student', 'id' => $student->id])" />
            </div>
        </div>
    </div>


    <script>
        (() => {
            const form = document.querySelector('form[action*="/ict-admin/students"]');
            if (!form) return;

            const fields = [
                {
                    input: form.querySelector('[name="first_name"]'),
                    validate: (value) => value.trim() ? '' : 'First name is required.'
                },
                {
                    input: form.querySelector('[name="last_name"]'),
                    validate: (value) => value.trim() ? '' : 'Last name is required.'
                },
                {
                    input: form.querySelector('[name="other_names"]'),
                    validate: (value) => value.length <= 255 ? '' : 'Other names must not exceed 255 characters.'
                },
                {
                    input: form.querySelector('[name="email"]'),
                    validate: (value, input) => {
                        if (!value.trim()) return '';
                        if (value.length > 255) return 'Email must not exceed 255 characters.';
                        if (input.validity.typeMismatch) return 'Enter a valid email address.';
                        return '';
                    }
                },
            ].filter(field => field.input);

            const baseInputClasses = 'w-full rounded-md border-gray-300 shadow-sm';

            function setState(input, message) {
                const existing = input.parentElement.querySelector('[data-live-error]');
                if (existing) existing.remove();

                input.classList.remove('border-red-500', 'ring-1', 'ring-red-200');

                if (message) {
                    input.classList.add('border-red-500', 'ring-1', 'ring-red-200');

                    const error = document.createElement('p');
                    error.dataset.liveError = 'true';
                    error.className = 'mt-1 text-xs text-red-600';
                    error.textContent = message;
                    input.parentElement.appendChild(error);
                }
            }

            function validate(field) {
                const value = field.input.value;
                setState(field.input, field.validate(value, field.input));
                return !field.validate(value, field.input);
            }

            fields.forEach(field => {
                field.input.addEventListener('input', () => validate(field));
                field.input.addEventListener('blur', () => validate(field));
            });

            form.addEventListener('submit', event => {
                let valid = true;

                fields.forEach(field => {
                    if (!validate(field)) valid = false;
                });

                if (!valid) {
                    event.preventDefault();
                    const firstInvalid = fields.find(field =>
                        field.input.classList.contains('border-red-500')
                    );
                    firstInvalid?.input.focus();
                }
            });
        })();
    </script>

    @if ($institution->education_level !== 'tertiary')
        <script>
            const classesData = @json($classes->keyBy('id'));
            const classSelect = document.getElementById('class_id');
            const armSelect = document.getElementById('arm_id');
            const currentArmId = '{{ old('arm_id', $student->arm_id) }}';

            function populateArms() {
                const selectedClass = classesData[classSelect.value];
                armSelect.innerHTML = '<option value="">Select arm</option>';

                if (selectedClass && selectedClass.arms) {
                    selectedClass.arms.forEach(arm => {
                        const option = document.createElement('option');
                        option.value = arm.id;
                        option.textContent = arm.name;
                        if (String(arm.id) === currentArmId) option.selected = true;
                        armSelect.appendChild(option);
                    });
                }
            }

            classSelect.addEventListener('change', populateArms);
            populateArms();
        </script>
    @endif
</x-app-layout>
