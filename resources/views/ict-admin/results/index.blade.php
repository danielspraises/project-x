<x-app-layout>
<div class="cx-stage min-h-screen overflow-hidden bg-slate-950 text-slate-100" x-data="resultsControlRoom()">
    <div class="pointer-events-none fixed inset-0 opacity-70">
        <div class="absolute -left-24 top-20 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>
        <div class="absolute right-0 top-1/3 h-96 w-96 rounded-full bg-violet-500/10 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 h-80 w-80 rounded-full bg-fuchsia-500/10 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-[1800px] px-4 py-6 sm:px-6 lg:px-8">
        <div class="cx-panel mb-6 overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-6 shadow-2xl backdrop-blur-2xl">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <div class="mb-2 inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-300">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span> RESULTS CONTROL ROOM
                    </div>
                    <h1 class="text-3xl font-black tracking-tight sm:text-4xl">Results</h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-400">Monitor submissions, verify academic results, publish approved grades and keep every change traceable.</p>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-[.18em] text-slate-500">Session</div>
                        <div class="mt-1 text-sm font-bold">{{ $sessions->firstWhere('id', $selectedSession)?->name ?? '—' }}</div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-[.18em] text-slate-500">Semester / Term</div>
                        <div class="mt-1 text-sm font-bold">{{ $terms->firstWhere('id', $selectedTerm)?->name ?? '—' }}</div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-[.18em] text-slate-500">Live</div>
                        <div class="mt-1 flex items-center gap-2 text-sm font-bold"><span class="h-2 w-2 rounded-full bg-emerald-400"></span> Operational</div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3">
                        <div class="text-[10px] uppercase tracking-[.18em] text-slate-500">Audit</div>
                        <div class="mt-1 text-sm font-bold">Enabled</div>
                    </div>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ url('/ict-admin/results') }}" class="cx-panel mb-6 rounded-[1.75rem] border border-white/10 bg-slate-900/70 p-4 shadow-xl backdrop-blur-xl">
            <div class="grid gap-3 lg:grid-cols-5">
                <select name="academic_session_id" class="cx-control rounded-xl border-white/10 bg-white/5 text-sm" onchange="this.form.submit()">
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" @selected($selectedSession == $session->id)>{{ $session->name }}</option>
                    @endforeach
                </select>
                <select name="term_id" class="cx-control rounded-xl border-white/10 bg-white/5 text-sm">
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}" @selected($selectedTerm == $term->id)>{{ $term->name }}</option>
                    @endforeach
                </select>

                <div class="relative" @click.outside="facultyOpen=false">
                    <button type="button" @click="facultyOpen=!facultyOpen" class="flex w-full items-center justify-between rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-left text-sm">
                        <span x-text="faculty || 'Faculty'" class="truncate"></span><span>⌄</span>
                    </button>
                    <div x-show="facultyOpen" x-transition class="absolute z-30 mt-2 w-full rounded-2xl border border-white/10 bg-slate-900 p-2 shadow-2xl">
                        <input x-model="facultySearch" placeholder="Search faculty..." class="mb-2 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm outline-none">
                        <button type="button" @click="faculty='';facultyOpen=false" class="block w-full rounded-lg px-3 py-2 text-left text-xs text-slate-400 hover:bg-white/10">All faculties</button>
                        @foreach($faculties as $item)
                            <button type="button" x-show="'{{ addslashes($item) }}'.toLowerCase().includes(facultySearch.toLowerCase())" @click="faculty='{{ addslashes($item) }}';facultyOpen=false" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-white/10">{{ $item }}</button>
                        @endforeach
                    </div>
                    <input type="hidden" name="faculty" :value="faculty">
                </div>

                <div class="relative" @click.outside="departmentOpen=false">
                    <button type="button" @click="departmentOpen=!departmentOpen" class="flex w-full items-center justify-between rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-left text-sm">
                        <span x-text="department || 'Department'" class="truncate"></span><span>⌄</span>
                    </button>
                    <div x-show="departmentOpen" x-transition class="absolute z-30 mt-2 w-full rounded-2xl border border-white/10 bg-slate-900 p-2 shadow-2xl">
                        <input x-model="departmentSearch" placeholder="Search department..." class="mb-2 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm outline-none">
                        <button type="button" @click="department='';departmentOpen=false" class="block w-full rounded-lg px-3 py-2 text-left text-xs text-slate-400 hover:bg-white/10">All departments</button>
                        @foreach($departments as $item)
                            <button type="button" x-show="'{{ addslashes($item) }}'.toLowerCase().includes(departmentSearch.toLowerCase())" @click="department='{{ addslashes($item) }}';departmentOpen=false" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-white/10">{{ $item }}</button>
                        @endforeach
                    </div>
                    <input type="hidden" name="department" :value="department">
                </div>

                <div class="relative">
                    <input name="course_search" value="{{ request('course_search') }}" placeholder="Search course code / title / lecturer" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none focus:border-cyan-400/50">
                </div>
            </div>
            <div class="mt-3 grid gap-3 md:grid-cols-4">
                <select name="level" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm"><option value="">All levels</option>@foreach($levels as $item)<option value="{{ $item }}" @selected(request('level') == $item)>{{ $item }}</option>@endforeach</select>
                <select name="programme" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm"><option value="">All programmes</option>@foreach($programmes as $item)<option value="{{ $item }}" @selected(request('programme') == $item)>{{ $item }}</option>@endforeach</select>
                <select name="status" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm"><option value="">All statuses</option>@foreach(['draft','submitted','returned','published','locked'] as $status)<option value="{{ $status }}" @selected(request('status') == $status)>{{ strtoupper($status) }}</option>@endforeach</select>
                <select name="verification_level" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm"><option value="">All verification levels</option>@foreach(['hod','class_teacher','ict'] as $level)<option value="{{ $level }}" @selected(request('verification_level') == $level)>{{ strtoupper(str_replace('_',' ',$level)) }}</option>@endforeach</select>
            </div>
            <div class="mt-3 flex flex-wrap justify-end gap-2">
                <a href="{{ url('/ict-admin/results') }}" class="rounded-xl border border-white/10 px-4 py-2 text-sm text-slate-300 hover:bg-white/10">Reset</a>
                <button class="rounded-xl bg-cyan-400 px-5 py-2 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20">Apply filters</button>
            </div>
        </form>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['Total Courses', $stats['total_courses'], 'Course/result sets currently tracked'],
                ['Submitted', $stats['submitted'], 'Awaiting or undergoing verification'],
                ['Pending ICT Audit', $stats['pending_ict'], 'HOD-approved and ready for ICT'],
                ['Approved & Published', $stats['published'], 'Published/locked result sets'],
            ] as [$label,$value,$hint])
                <div class="cx-card group rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5 shadow-xl transition duration-500 hover:-translate-y-1 hover:rotate-[.3deg] hover:bg-white/[0.09]" data-cx-tilt>
                    <div class="text-xs uppercase tracking-[.18em] text-slate-500">{{ $label }}</div>
                    <div class="mt-2 text-4xl font-black">{{ $value }}</div>
                    <div class="mt-2 text-xs text-slate-400">{{ $hint }}</div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_340px]">
            <form method="POST" action="{{ url('/ict-admin/results/batch-publish') }}" class="cx-panel overflow-hidden rounded-[1.75rem] border border-white/10 bg-slate-900/75 shadow-2xl">
                @csrf
                <div class="flex flex-col gap-3 border-b border-white/10 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div><h2 class="text-lg font-black">Submission pipeline</h2><p class="text-xs text-slate-500">Select HOD-approved submissions for controlled ICT publication.</p></div>
                    <div class="flex gap-2"><button type="button" @click="selectAll()" class="rounded-lg border border-white/10 px-3 py-2 text-xs">Select eligible</button><button class="rounded-lg bg-violet-400 px-3 py-2 text-xs font-black text-slate-950">Publish selected</button></div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[1100px] w-full text-left text-sm">
                        <thead class="bg-white/[0.03] text-[10px] uppercase tracking-[.16em] text-slate-500"><tr><th class="px-5 py-3"></th><th class="px-5 py-3">Course</th><th class="px-5 py-3">Lecturer / Evaluator</th><th class="px-5 py-3">Enrolled</th><th class="px-5 py-3">Upload Status</th><th class="px-5 py-3">Verification</th><th class="px-5 py-3">Last Updated</th><th class="px-5 py-3">Action</th></tr></thead>
                        <tbody class="divide-y divide-white/5">
                        @forelse($rows as $row)
                            @php
                                $course = data_get($row,'courseOffering.course');
                                $subject = data_get($row,'subjectOffering.subject');
                                $courseLabel = $course ? ($course->code.' — '.$course->title) : ($subject ? (($subject->code ?? '').' — '.($subject->name ?? '')) : 'Result set #'.$row->id);
                                $hodApproved = $row->verifications->contains(fn($v) => $v->level === 'hod' && $v->decision === 'approved');
                                $enrolled = $course ? $row->courseOffering->registrations()->count() : $row->subjectOffering->registrations()->count();
                            @endphp
                            <tr class="transition hover:bg-white/[0.035]">
                                <td class="px-5 py-4"><input type="checkbox" name="submission_ids[]" value="{{ $row->id }}" x-model="selected" @disabled(!($row->status === 'submitted' && $hodApproved)) class="rounded border-white/20 bg-white/5"></td>
                                <td class="px-5 py-4"><div class="font-bold">{{ $courseLabel }}</div><div class="mt-1 text-xs text-slate-500">{{ data_get($row,'courseOffering.programme.name') ?? data_get($row,'subjectOffering.class.name') ?? '—' }}</div></td>
                                <td class="px-5 py-4">{{ $row->submittedBy?->name ?? '—' }}</td>
                                <td class="px-5 py-4 font-semibold">{{ $enrolled }}</td>
                                <td class="px-5 py-4"><span class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase {{ $row->status === 'published' || $row->status === 'locked' ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-300' : ($row->status === 'returned' ? 'border-rose-400/20 bg-rose-400/10 text-rose-300' : 'border-amber-400/20 bg-amber-400/10 text-amber-300') }}">{{ $row->status }}</span></td>
                                <td class="px-5 py-4"><span class="text-xs {{ $hodApproved ? 'text-cyan-300' : 'text-slate-400' }}">{{ $hodApproved ? 'HOD APPROVED' : 'REVIEW REQUIRED' }}</span></td>
                                <td class="px-5 py-4 text-xs text-slate-400">{{ $row->updated_at?->format('d M Y, H:i') }}</td>
                                <td class="px-5 py-4"><a href="{{ url('/ict-admin/results?submission_id='.$row->id) }}" class="rounded-lg border border-white/10 px-3 py-2 text-xs hover:bg-white/10">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-5 py-14 text-center text-sm text-slate-500">No result submissions match the current filters.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            <aside class="space-y-4">
                <div class="cx-panel rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5">
                    <div class="flex items-center justify-between"><div><h3 class="font-black">System alerts</h3><p class="text-xs text-slate-500">Unresolved result-engine alerts</p></div><span class="rounded-full bg-rose-400/10 px-2 py-1 text-[10px] font-bold text-rose-300">{{ $alerts->count() }}</span></div>
                    <div class="mt-4 space-y-3">
                        @forelse($alerts as $alert)
                            <div class="rounded-xl border border-white/10 bg-slate-950/50 p-3"><div class="text-xs font-bold">{{ $alert->title }}</div><div class="mt-1 text-xs leading-5 text-slate-400">{{ $alert->message }}</div></div>
                        @empty
                            <div class="rounded-xl border border-emerald-400/10 bg-emerald-400/5 p-3 text-xs text-emerald-300">No unresolved alerts.</div>
                        @endforelse
                    </div>
                </div>
                <div class="cx-panel rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5">
                    <h3 class="font-black">Governance tools</h3>
                    <div class="mt-4 grid gap-2"><a href="{{ url('/ict-admin/results/audit-logs') }}" class="rounded-xl border border-white/10 px-4 py-3 text-sm hover:bg-white/10">Audit Logs <span class="float-right">↗</span></a><a href="{{ url('/ict-admin/results/senate-summary') }}" class="rounded-xl border border-white/10 px-4 py-3 text-sm hover:bg-white/10">Senate Summary <span class="float-right">↗</span></a><a href="{{ url('/ict-admin/results/corrections') }}" class="rounded-xl border border-white/10 px-4 py-3 text-sm hover:bg-white/10">Correction Queue <span class="float-right">↗</span></a></div>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
function resultsControlRoom() {
    return {
        selected: [], faculty: @json(request('faculty','')), department: @json(request('department','')),
        facultySearch: '', departmentSearch: '', facultyOpen: false, departmentOpen: false,
        selectAll() {
            this.selected = [...document.querySelectorAll('input[name="submission_ids[]"]:not(:disabled)')].map(el => el.value);
        }
    }
}
</script>
</x-app-layout>
