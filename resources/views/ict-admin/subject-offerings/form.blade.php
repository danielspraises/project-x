@php
    $selectedSubjectId = old('subject_id', $subjectOffering->subject_id ?? $selectedSubject?->id ?? '');
    $selectedSubjectForDisplay = $selectedSubject ?? $subjectOffering?->subject;

    $selectedLeadId = old(
        'lead_teacher_id',
        optional($subjectOffering?->teachers->firstWhere('pivot.role', 'lead'))->id ?? ''
    );

    $selectedSupportingIds = array_values(array_unique(array_map(
        'strval',
        old('teacher_ids', $subjectOffering?->teachers->pluck('id')->all() ?? [])
    )));

    $selectedSupportingTeachers = $subjectOffering?->teachers
        ->filter(fn ($teacher) => in_array((string) $teacher->id, $selectedSupportingIds, true))
        ->values() ?? collect();

    if ($selectedLeadId && $selectedSupportingTeachers->isNotEmpty()) {
        $selectedSupportingTeachers = $selectedSupportingTeachers
            ->reject(fn ($teacher) => (string) $teacher->id === (string) $selectedLeadId)
            ->values();
    }

    $selectedScope = old('scope', $subjectOffering->scope ?? ($defaultScope ?? 'class'));
    $selectedRequirementType = old('requirement_type', $subjectOffering->requirement_type ?? 'compulsory');
@endphp

<div class="space-y-5">
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="cx-panel rounded-2xl">
        <form method="POST" action="{{ $action }}">
            @csrf
            @if($method === 'PUT') @method('PUT') @endif

            <div class="p-5 sm:p-6">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="academic_session_id" class="ui-label mb-1.5">Academic session</label>
                        <select id="academic_session_id" name="academic_session_id" class="cx-input w-full rounded-xl" required>
                            <option value="">Select session</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}" @selected(old('academic_session_id', $subjectOffering->academic_session_id ?? '') == $session->id)>
                                    {{ $session->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="term_id" class="ui-label mb-1.5">Term</label>
                        <select id="term_id" name="term_id" class="cx-input w-full rounded-xl" required>
                            <option value="">Select term</option>
                            @foreach($sessions as $session)
                                <optgroup label="{{ $session->name }}">
                                    @foreach($session->terms as $term)
   <option value="{{ $term->id }}" @selected(old('term_id', $subjectOffering->term_id ?? '') == $term->id)>
       {{ $term->name }}
   </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="class_id" class="ui-label mb-1.5">Class</label>
                        <select id="class_id" name="class_id" class="cx-input w-full rounded-xl" required>
                            <option value="">Select class</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $subjectOffering->class_id ?? '') == $class->id)>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="arm_id" class="ui-label mb-1.5">Arm</label>
                        <select name="arm_id" id="arm_id" class="cx-inputw-full rounded-xl">
                            <option value="">Select arm</option>
                        </select>
                    </div>
                </div>

                <div class="mt-5">
                    <label class="ui-label mb-2">Teaching scope</label>
                    <div class="flexflex-wrap gap-5">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="radio" name="scope" value="class" class="h-4 w-4 border-gray-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" @checked($selectedScope === 'class')>
                            <span>Whole class</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="radio" name="scope" value="arm" class="h-4 w-4 border-gray-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" @checked($selectedScope === 'arm')>
                            <span>Specific arm</span>
                        </label>
                    </div>
                </div>

                <div class="mt-5">
                    <label class="ui-label mb-2">Requirement</label>
                    <div class="flex flex-wrap gap-5">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="radio" name="requirement_type" value="compulsory" class="h-4 w-4 border-gray-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" @checked($selectedRequirementType === 'compulsory')>
                            <span>Compulsory <span class="text-slate-500 font-normal">— every eligible student is registered automatically</span></span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="radio" name="requirement_type" value="elective" class="h-4 w-4 border-gray-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" @checked($selectedRequirementType === 'elective')>
                            <span>Elective <span class="text-slate-500 font-normal">— students are registered individually</span></span>
                        </label>
                    </div>
                    @if($subjectOffering?->exists && $selectedRequirementType === 'elective')
                        <p class="mt-2 text-xs text-slate-500">
                            Manage which students take this elective from the
                            <a href="{{ route('ict-admin.subject-offerings.registrations.edit', $subjectOffering) }}" class="text-[var(--brand-primary)] underline">student registration page</a>.
                        </p>
                    @endif
                </div>

                <div class="relativez-40 mt-5 mb-8">
                    <label for="subject-search" class="ui-label mb-1.5">Subject</label>
                    <div class="relative" data-subject-picker>
                        <input type="hidden" name="subject_id" id="subject-id" value="{{ $selectedSubjectId }}" required>
                        <input type="search" id="subject-search" class="cx-input w-full rounded-xl" placeholder="Search subject by name or code" autocomplete="off">
                        <div id="subject-selected" class="mt-2 {{ $selectedSubjectForDisplay ? '' : 'hidden' }}">
                            @if($selectedSubjectForDisplay)
                                <span class="inline-flex items-center gap-2 rounded-lg border border-[var(--line)] bg-[var(--control)] px-3 py-2 text-sm">
                                    <span class="truncate">{{ $selectedSubjectForDisplay->code ? $selectedSubjectForDisplay->code.' — ' : '' }}{{$selectedSubjectForDisplay->name }}</span>
                                    <button type="button" id="subject-clear" class="text-lg leading-none text-slate-400" aria-label="Clear subject">&times;</button>
                                </span>
                            @endif
                        </div>
                        <div id="subject-results" class="cx-panel-solid absolute left-0 right-0 z-30 mt-1 hidden max-h-64 overflow-y-auto rounded-xl p-1"></div>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div>
                        <label for="lead-teacher-search" class="ui-label mb-1.5">Lead teacher <span class="font-normal text-slate-500">(optional)</span></label>
                        <div class="relative" data-teacher-picker="lead">
                            <input type="hidden" name="lead_teacher_id" id="lead-teacher-id" value="{{ $selectedLeadId }}">
                            <input type="search" id="lead-teacher-search"class="cx-input w-full rounded-xl" placeholder="Search teacher" autocomplete="off">
                            <div id="lead-teacher-selected" class="mt-2 {{ $selectedLeadId ? '' : 'hidden' }}">
                                @if($selectedLeadId && $subjectOffering?->teachers->firstWhere('id', $selectedLeadId))
                                    <span class="inline-flex items-centergap-2 rounded-lg border border-[var(--line)] bg-[var(--control)] px-3 py-2 text-sm">
   <span class="truncate">{{ $subjectOffering->teachers->firstWhere('id',$selectedLeadId)->name }}</span>
   <button type="button" id="lead-teacher-clear" class="text-lg leading-none text-slate-400" aria-label="Clearlead teacher">&times;</button>
                                    </span>
                                @endif
                            </div>
                            <div id="lead-teacher-results" class="cx-panel-solid absolute left-0 right-0 z-30 mt-1 hidden max-h-64 overflow-y-auto rounded-xl p-1"></div>
                        </div>
                    </div>

                    <div>
                        <label for="supporting-teacher-search" class="ui-label mb-1.5">Supporting teachers <span class="font-normal text-slate-500">(optional)</span></label>
                        <div class="relative" data-teacher-picker="supporting">
                            <input type="search" id="supporting-teacher-search" class="cx-input w-full rounded-xl" placeholder="Search teacher" autocomplete="off">
                            <div id="supporting-teacher-selected" class="mt-2 flex flex-wrap gap-2 {{ $selectedSupportingTeachers->isEmpty() ? 'hidden' : '' }}">
                                @foreach($selectedSupportingTeachers as $teacher)
                                    <span class="inline-flex items-centergap-2 rounded-lg border border-[var(--line)] bg-[var(--control)] px-3 py-2 text-sm">
   <input type="hidden" name="teacher_ids[]" value="{{ $teacher->id }}">
   <span class="truncate">{{ $teacher->name }}</span>
   <button type="button" class="supporting-remove text-lg leading-none text-slate-400" data-id="{{ $teacher->id }}" aria-label="Remove teacher">&times;</button>
                                    </span>
                                @endforeach
                            </div>
                            <div id="supporting-teacher-results" class="cx-panel-solid absolute left-0 right-0z-30 mt-1 hidden max-h-64 overflow-y-auto rounded-xl p-1"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-[var(--line)] px-5 py-4 sm:px-6">
                <a href="{{ route('ict-admin.subject-offerings.index') }}" class="cx-button rounded-xl px-4 py-2 text-sm">Cancel</a>
                <button type="submit" class="btn-brand-primary rounded-xlpx-5 py-2 text-sm font-semibold">
                    {{ $method === 'PUT' ? 'Update' : 'Save' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const classArms = @json($classes->mapWithKeys(fn($c) => [$c->id => $c->arms->map(fn($a) => ['id'=>$a->id,'name'=>$a->name])])->all());
const armSelect = document.getElementById('arm_id');
const classSelect = document.getElementById('class_id');
const oldArm = @json(old('arm_id', $subjectOffering->arm_id ?? null));

function refreshArms() {
    const id = classSelect.value;
    armSelect.innerHTML = '<option value="">Select arm</option>';
    (classArms[id] || []).forEach(a => {
        const option = new Option(a.name, a.id);
        if (String(a.id) === String(oldArm)) option.selected = true;
        armSelect.add(option);
    });
    const armScope = document.querySelector('input[name="scope"]:checked')?.value === 'arm';
    armSelect.required = armScope;
    armSelect.disabled = !armScope;
}
classSelect.addEventListener('change', refreshArms);
document.querySelectorAll('input[name="scope"]').forEach(input => input.addEventListener('change', refreshArms));
refreshArms();

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, char => ({
        '&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'
    }[char]));
}

async function fetchSearch(url, q) {
    const response = await fetch(`${url}?q=${encodeURIComponent(q)}`, {
        headers: {'Accept':'application/json'}
    });
    if (!response.ok) throw new Error('Search failed');
    const payload = await response.json();
    return payload.data || [];
}

const subjectSearchUrl = @json($subjectSearchUrl);
const subjectId = document.getElementById('subject-id');
const subjectSearch = document.getElementById('subject-search');
const subjectResults = document.getElementById('subject-results');
const subjectSelected = document.getElementById('subject-selected');
let subjectTimer = null;
let subjectToken = 0;

function setSubject(id, label) {
    subjectId.value = id || '';
    if (id) {
        subjectSelected.innerHTML = `<span class="inline-flex items-center gap-2 rounded-lg border border-[var(--line)] bg-[var(--control)] px-3 py-2 text-sm"><span class="truncate">${escapeHtml(label)}</span><button type="button" id="subject-clear" class="text-lg leading-none text-slate-400" aria-label="Clear subject">&times;</button></span>`;
        subjectSelected.classList.remove('hidden');
        document.getElementById('subject-clear').addEventListener('click', () => setSubject('', ''));
    } else {
        subjectSelected.innerHTML = '';
        subjectSelected.classList.add('hidden');
    }
}

async function searchSubjects() {
    const q = subjectSearch.value.trim();
    if (q.length < 2) {
        subjectResults.classList.add('hidden');
        return;
    }
    const token = ++subjectToken;
    subjectResults.innerHTML = '<divclass="px-3 py-2 text-sm text-slate-500">Searching…</div>';
    subjectResults.classList.remove('hidden');
    try {
        const items = await fetchSearch(subjectSearchUrl, q);
        if (token !== subjectToken) return;
        subjectResults.innerHTML = items.length
            ? items.map(subject => {
                const label = subject.label || ((subject.code ? subject.code + ' — ' : '') + subject.name);
                return `<button type="button" class="block w-full rounded-lg px-3 py-2 text-left" data-id="${escapeHtml(subject.id)}" data-label="${escapeHtml(label)}"><span class="block text-sm font-medium">${escapeHtml(subject.name)}</span><span class="block text-xs text-slate-500">${escapeHtml(subject.code || '')}</span></button>`;
            }).join('')
            : '<div class="px-3 py-2text-sm text-slate-500">No matching subjects.</div>';
        subjectResults.querySelectorAll('button').forEach(button => button.addEventListener('click', () => {
            setSubject(button.dataset.id, button.dataset.label);
            subjectResults.classList.add('hidden');
            subjectSearch.value = '';
        }));
    } catch {
        if (token === subjectToken) subjectResults.innerHTML = '<div class="px-3 py-2 text-sm text-red-600">Unable to search.</div>';
    }
}
subjectSearch.addEventListener('input', () => {
    clearTimeout(subjectTimer);
    subjectTimer = setTimeout(searchSubjects, 250);
});

const teacherSearchUrl = @json($teacherSearchUrl);
const leadId = document.getElementById('lead-teacher-id');
const leadSearch = document.getElementById('lead-teacher-search');
const leadResults = document.getElementById('lead-teacher-results');
const leadSelected = document.getElementById('lead-teacher-selected');
let leadTimer = null;
let leadToken = 0;

function setLeadTeacher(id, name) {
    if (id) {
        selectedSupporting.delete(String(id));
        if (typeof renderSupportingSelected === 'function') renderSupportingSelected();
    }
    leadId.value = id || '';
    if (id) {
        leadSelected.innerHTML = `<span class="inline-flex items-center gap-2 rounded-lg border border-[var(--line)] bg-[var(--control)] px-3 py-2 text-sm"><span class="truncate">${escapeHtml(name)}</span><button type="button" id="lead-teacher-clear" class="text-lg leading-none text-slate-400" aria-label="Clear lead teacher">&times;</button></span>`;
        leadSelected.classList.remove('hidden');
        document.getElementById('lead-teacher-clear').addEventListener('click', () => setLeadTeacher('', ''));
    } else {
        leadSelected.innerHTML = '';
        leadSelected.classList.add('hidden');
    }
}

async function searchLeadTeachers() {
    const q = leadSearch.value.trim();
    if (q.length < 2) {
        leadResults.classList.add('hidden');
        return;
    }
    const token = ++leadToken;
    leadResults.innerHTML = '<div class="px-3 py-2 text-sm text-slate-500">Searching…</div>';
    leadResults.classList.remove('hidden');
    try {
        const items = await fetchSearch(teacherSearchUrl, q);
        if (token !== leadToken) return;
        leadResults.innerHTML = items.length
            ? items.map(teacher => `<button type="button" class="block w-full rounded-lg px-3 py-2 text-left" data-id="${escapeHtml(teacher.id)}" data-name="${escapeHtml(teacher.name)}"><span class="block text-sm font-medium">${escapeHtml(teacher.name)}</span><span class="block text-xs text-slate-500">${escapeHtml(teacher.email ||'')}</span></button>`).join('')
            : '<div class="px-3 py-2text-sm text-slate-500">No matching teachers.</div>';
        leadResults.querySelectorAll('button').forEach(button => button.addEventListener('click', () => {
            setLeadTeacher(button.dataset.id, button.dataset.name);
            leadResults.classList.add('hidden');
            leadSearch.value = '';
        }));
    } catch {
        if (token === leadToken) leadResults.innerHTML = '<div class="px-3 py-2 text-sm text-red-600">Unable to search.</div>';
    }
}
leadSearch.addEventListener('input',() => {
    clearTimeout(leadTimer);
    leadTimer = setTimeout(searchLeadTeachers, 250);
});

const supportingSearch = document.getElementById('supporting-teacher-search');
const supportingResults = document.getElementById('supporting-teacher-results');
const supportingSelected = document.getElementById('supporting-teacher-selected');
const selectedSupporting = new Map();

supportingSelected.querySelectorAll('input[name="teacher_ids[]"]').forEach(input => {
    const id = String(input.value);
    if (id !== String(leadId.value || '')) {
        selectedSupporting.set(id, input.parentElement.querySelector('span').textContent.trim());
    }
});

function renderSupportingSelected() {
    supportingSelected.innerHTML = '';
    selectedSupporting.forEach((name, id) => {
        supportingSelected.insertAdjacentHTML('beforeend',
            `<span class="inline-flex items-center gap-2 rounded-lg border border-[var(--line)] bg-[var(--control)] px-3 py-2 text-sm"><input type="hidden" name="teacher_ids[]" value="${escapeHtml(id)}"><span class="truncate">${escapeHtml(name)}</span><button type="button" class="supporting-remove text-lg leading-none text-slate-400" data-id="${escapeHtml(id)}" aria-label="Remove teacher">&times;</button></span>`
        );
    });
    supportingSelected.classList.toggle('hidden', selectedSupporting.size=== 0);
    supportingSelected.querySelectorAll('.supporting-remove').forEach(button => {
        button.addEventListener('click', () => {
            selectedSupporting.delete(String(button.dataset.id));
            renderSupportingSelected();
        });
    });
}

async function searchSupportingTeachers() {
    const q = supportingSearch.value.trim();
    if (q.length < 2) {
        supportingResults.classList.add('hidden');
        return;
    }
    supportingResults.innerHTML = '<div class="px-3 py-2 text-sm text-slate-500">Searching…</div>';
    supportingResults.classList.remove('hidden');
    try {
        const items = await fetchSearch(teacherSearchUrl, q);
        supportingResults.innerHTML = items.length
            ? items.map(teacher => {
                const id = String(teacher.id);
                if (id === String(leadId.value || '')) return '';
                const selected = selectedSupporting.has(id);
                return `<button type="button" class="block w-full rounded-lg px-3 py-2 text-left" data-id="${escapeHtml(id)}" data-name="${escapeHtml(teacher.name)}"><span class="flex items-center gap-2 text-sm"><span class="h-4 w-4 rounded border ${selected? 'bg-[var(--brand-primary)] border-[var(--brand-primary)]' : 'border-gray-300'}"></span><span class="truncatefont-medium">${escapeHtml(teacher.name)}</span></span><span class="ml-6 block text-xs text-slate-500">${escapeHtml(teacher.email || '')}</span></button>`;
            }).join('')
            : '<div class="px-3 py-2text-sm text-slate-500">No matching teachers.</div>';

        supportingResults.querySelectorAll('button').forEach(button => button.addEventListener('click', () => {
            const id = String(button.dataset.id);
            if (selectedSupporting.has(id)) selectedSupporting.delete(id);
            else selectedSupporting.set(id, button.dataset.name);
            renderSupportingSelected();
            searchSupportingTeachers();
        }));
    } catch {
        supportingResults.innerHTML = '<div class="px-3 py-2 text-sm text-red-600">Unable to search.</div>';
    }
}
supportingSearch.addEventListener('input', searchSupportingTeachers);

document.addEventListener('click', event => {
    if (!event.target.closest('[data-subject-picker]')) subjectResults.classList.add('hidden');
    if (!event.target.closest('[data-teacher-picker="lead"]')) leadResults.classList.add('hidden');
    if (!event.target.closest('[data-teacher-picker="supporting"]')) supportingResults.classList.add('hidden');
});

const existingSubjectClear = document.getElementById('subject-clear');
if (existingSubjectClear) existingSubjectClear.addEventListener('click', () => setSubject('', ''));

const existingLeadClear = document.getElementById('lead-teacher-clear');
if (existingLeadClear) existingLeadClear.addEventListener('click', () => setLeadTeacher('', ''));

supportingSelected.querySelectorAll('.supporting-remove').forEach(button => {
    button.addEventListener('click',() => {
        selectedSupporting.delete(String(button.dataset.id));
        renderSupportingSelected();
    });
});
</script>
