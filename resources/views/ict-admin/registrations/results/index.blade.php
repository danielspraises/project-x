<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Result Entry</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Enter draft results only for students and subjects/courses within your assigned scope.
            </p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="GET" action="{{ route('ict-admin.results.index') }}" class="grid gap-4 md:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Academic session</label>
                    <select name="academic_session_id" onchange="this.form.submit()" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        @foreach ($sessions as $session)
                            <option value="{{ $session->id }}" @selected($selectedSession == $session->id)>{{ $session->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Term</label>
                    <select name="term_id" onchange="this.form.submit()" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        @foreach ($terms as $term)
                            <option value="{{ $term->id }}" @selected($selectedTerm == $term->id)>{{ $term->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($mode === 'basic')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Class</label>
                        <select name="class_id" onchange="this.form.submit()" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                            <option value="">All accessible classes</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Subject offering</label>
                        <select name="subject_offering_id" onchange="this.form.submit()" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                            <option value="">Choose subject</option>
                            @foreach ($offerings as $offering)
                                <option value="{{ $offering->id }}" @selected(request('subject_offering_id') == $offering->id)>
                                    {{ $offering->subject?->name }} — {{ $offering->schoolClass?->name }}{{ $offering->arm ? ' / '.$offering->arm->name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Course offering</label>
                        <select name="course_offering_id" onchange="this.form.submit()" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                            <option value="">Choose course</option>
                            @foreach ($courseOfferings as $offering)
                                <option value="{{ $offering->id }}" @selected(request('course_offering_id') == $offering->id)>
                                    {{ $offering->course?->code }} — {{ $offering->course?->name }} ({{ $offering->level }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </form>
        </section>

        @if ($selectedOffering && $students->isNotEmpty())
            <section class="space-y-3">
                <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                        @if ($mode === 'basic')
                            {{ $selectedOffering->subject?->name }}
                        @else
                            {{ $selectedOffering->course?->code }} — {{ $selectedOffering->course?->name }}
                        @endif
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $students->count() }} student(s) · Draft entry
                    </p>
                </div>

                @foreach ($students as $student)
                    @php
                        $result = $existingResults->get($student->id);
                        $courseRegistrationId = $mode === 'tertiary'
                            ? $student->courseRegistrations()->where('course_offering_id', $selectedOffering->id)->value('id')
                            : null;
                    @endphp

                    <form method="POST"
                          action="{{ $result ? route('ict-admin.results.update', $result) : route('ict-admin.results.store') }}"
                          class="result-entry-form rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        @csrf
                        @if ($result) @method('PUT') @endif

                        <input type="hidden" name="institution_id" value="{{ auth()->user()->institution_id }}">
                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                        <input type="hidden" name="academic_session_id" value="{{ $selectedSession }}">
                        <input type="hidden" name="term_id" value="{{ $selectedTerm }}">
                        <input type="hidden" name="status" value="{{ $result?->status ?? 'draft' }}">

                        @if ($mode === 'basic')
                            <input type="hidden" name="class_id" value="{{ $selectedOffering->class_id }}">
                            <input type="hidden" name="arm_id" value="{{ $selectedOffering->arm_id ?? $student->arm_id }}">
                            <input type="hidden" name="subject_offering_id" value="{{ $selectedOffering->id }}">
                        @else
                            <input type="hidden" name="course_registration_id" value="{{ $courseRegistrationId }}">
                            <input type="hidden" name="course_offering_id" value="{{ $selectedOffering->id }}">
                        @endif

                        <div class="grid gap-5 md:grid-cols-5 md:items-end">
                            <div class="md:col-span-2">
                                <div class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $student->last_name }}, {{ $student->first_name }} {{ $student->other_names }}
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    {{ $student->admission_number ?? $student->matric_number }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">CA</label>
                                <input name="ca_score" value="{{ old('ca_score', $result?->ca_score) }}"
                                       type="number" min="0" max="100" step="0.01"
                                       class="score-input mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Exam</label>
                                <input name="exam_score" value="{{ old('exam_score', $result?->exam_score) }}"
                                       type="number" min="0" max="100" step="0.01"
                                       class="score-input mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Total</label>
                                <input name="total_score" value="{{ old('total_score', $result?->total_score) }}"
                                       type="number" min="0" max="100" step="0.01" readonly
                                       class="total-input mt-1 w-full rounded-lg border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-700">
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Status: {{ ucfirst($result?->status ?? 'Draft') }}
                            </span>
                            <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background: var(--color-primary);">
                                {{ $result ? 'Update draft' : 'Save draft' }}
                            </button>
                        </div>
                    </form>
                @endforeach
            </section>
        @elseif ($selectedOffering)
            <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                No students are currently available for this result context.
            </div>
        @else
            <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                Choose the academic period and a subject/course to begin result entry.
            </div>
        @endif
    </div>

    <script>
        document.querySelectorAll('.result-entry-form').forEach(function (form) {
            const ca = form.querySelector('[name="ca_score"]');
            const exam = form.querySelector('[name="exam_score"]');
            const total = form.querySelector('[name="total_score"]');

            const calculate = function () {
                const caValue = parseFloat(ca.value);
                const examValue = parseFloat(exam.value);

                if (!Number.isNaN(caValue) && !Number.isNaN(examValue)) {
                    total.value = (caValue + examValue).toFixed(2);
                } else {
                    total.value = '';
                }
            };

            ca.addEventListener('input', calculate);
            exam.addEventListener('input', calculate);
            calculate();
        });
    </script>
</x-app-layout>
