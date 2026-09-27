<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Default Class Teacher</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-5">
                    A class-wide default applies to all arms. An arm-level default overrides it for that arm.
                    Subject-specific teachers always take priority.
                </p>

                <form method="POST" action="{{ route('ict-admin.class-teachers.bulk') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Academic Session</label>
                        <select name="academic_session_id" class="w-full rounded-md border-gray-300" required>
                            @foreach($sessions as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">{{ auth()->user()->institution->periodLabel() }}</label>
                        <select name="term_id" class="w-full rounded-md border-gray-300" required>
                            @foreach($sessions as $s)
                                <optgroup label="{{ $s->name }}">
                                    @foreach($s->terms as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Class</label>
                        <select name="class_id" id="ct_class" class="w-full rounded-md border-gray-300" required>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Scope</label>
                        <select name="scope" id="ct_scope" class="w-full rounded-md border-gray-300">
                            <option value="class">Whole class — all arms</option>
                            <option value="arm">Selected arms</option>
                        </select>
                    </div>

                    <div id="ct_arms">
                        <div class="flex justify-between">
                            <label class="block text-sm text-gray-600 mb-1">Arms</label>
                            <span class="text-xs">
                                <button type="button" onclick="allArms(true)" class="text-brand mr-2">Select all</button>
                                <button type="button" onclick="allArms(false)" class="text-brand">Clear</button>
                            </span>
                        </div>
                        <div id="arm_boxes" class="border rounded-md p-3 grid grid-cols-2 gap-2"></div>
                    </div>

                    <div>
                        <label for="ct_teacher_search" class="block text-sm text-gray-600 mb-1">Teacher</label>
                        <div class="relative">
                            <input
                                type="text"
                                id="ct_teacher_search"
                                autocomplete="off"
                                placeholder="Search teacher by name or email…"
                                class="w-full rounded-md border-gray-300"
                                aria-controls="ct_teacher_results"
                                aria-expanded="false"
                                required
                            >
                            <input type="hidden" name="teacher_id" id="ct_teacher_id" value="">
                            <div
                                id="ct_teacher_results"
                                class="hidden absolute z-30 mt-1 w-full overflow-hidden rounded-md border border-gray-200 bg-white shadow-lg"
                                role="listbox"
                            ></div>
                        </div>
                        <p id="ct_teacher_hint" class="mt-1 text-xs text-gray-500">
                            Start typing to search eligible teachers.
                        </p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('ict-admin.class-teachers.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                        <x-btn-primary type="submit">Save Assignment(s)</x-btn-primary>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @php
        $classArmsForJs = $classes->mapWithKeys(function ($c) {
            return [$c->id => $c->arms->map(function ($a) {
                return ['id' => $a->id, 'name' => $a->name];
            })->values()->all()];
        })->all();

        $teachersForJs = $teachers->map(function ($t) {
            return ['id' => $t->id, 'name' => $t->name, 'email' => $t->email];
        })->values()->all();
    @endphp

    <script>
        const classArms = {!! json_encode($classArmsForJs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
        const teachers = {!! json_encode($teachersForJs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};

        const cls = document.getElementById('ct_class');
        const scope = document.getElementById('ct_scope');
        const boxes = document.getElementById('arm_boxes');
        const teacherSearch = document.getElementById('ct_teacher_search');
        const teacherId = document.getElementById('ct_teacher_id');
        const teacherResults = document.getElementById('ct_teacher_results');
        const teacherHint = document.getElementById('ct_teacher_hint');

        function renderArms() {
            boxes.innerHTML = (classArms[cls.value] || []).map(a => `
                <label class="text-sm">
                    <input type="checkbox" name="arm_ids[]" value="${a.id}" class="arm-choice rounded border-gray-300">
                    ${a.name}
                </label>
            `).join('');

            document.getElementById('ct_arms').style.display = scope.value === 'arm' ? 'block' : 'none';
        }

        function allArms(value) {
            document.querySelectorAll('.arm-choice').forEach(x => x.checked = value);
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function closeTeacherResults() {
            teacherResults.classList.add('hidden');
            teacherSearch.setAttribute('aria-expanded', 'false');
        }

        function showTeacherResults() {
            const query = teacherSearch.value.trim().toLowerCase();

            const matches = teachers
                .filter(t => {
                    const name = (t.name || '').toLowerCase();
                    const email = (t.email || '').toLowerCase();
                    return !query || name.includes(query) || email.includes(query);
                })
                .slice(0, 10);

            teacherResults.innerHTML = matches.length
                ? matches.map(t => `
                    <button type="button"
                        class="block w-full border-b border-gray-100 px-3 py-2 text-left last:border-0 hover:bg-gray-50"
                        data-teacher-id="${t.id}">
                        <span class="block text-sm font-medium text-gray-800">${escapeHtml(t.name || '')}</span>
                        ${t.email ? `<span class="block text-xs text-gray-500">${escapeHtml(t.email)}</span>` : ''}
                    </button>
                `).join('')
                : '<div class="px-3 py-2 text-sm text-gray-500">No matching teacher found.</div>';

            teacherResults.classList.remove('hidden');
            teacherSearch.setAttribute('aria-expanded', 'true');
        }

        function selectTeacher(id) {
            const teacher = teachers.find(t => String(t.id) === String(id));
            if (!teacher) return;

            teacherId.value = teacher.id;
            teacherSearch.value = teacher.name;
            teacherHint.textContent = teacher.email || 'Teacher selected.';
            closeTeacherResults();
        }

        teacherSearch.addEventListener('focus', showTeacherResults);

        teacherSearch.addEventListener('input', () => {
            teacherId.value = '';
            teacherHint.textContent = 'Start typing to search eligible teachers.';
            showTeacherResults();
        });

        teacherResults.addEventListener('click', event => {
            const button = event.target.closest('[data-teacher-id]');
            if (button) selectTeacher(button.dataset.teacherId);
        });

        teacherSearch.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeTeacherResults();
        });

        document.addEventListener('click', event => {
            if (!teacherSearch.contains(event.target) && !teacherResults.contains(event.target)) {
                closeTeacherResults();
            }
        });

        document.querySelector('form').addEventListener('submit', event => {
            if (!teacherId.value) {
                event.preventDefault();
                teacherHint.textContent = 'Select a teacher from the search results.';
                teacherSearch.focus();
                showTeacherResults();
            }
        });

        cls.addEventListener('change', renderArms);
        scope.addEventListener('change', renderArms);
        renderArms();
    </script>
</x-app-layout>
