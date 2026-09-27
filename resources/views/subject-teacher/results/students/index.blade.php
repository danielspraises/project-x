<x-app-layout>
<div class="cx-stage min-h-screen overflow-hidden bg-slate-950 text-slate-100"
     x-data="{
        search: '',
        students: @js($students),
        get filtered() {
            if (!this.search) return this.students;
            const q = this.search.toLowerCase();
            return this.students.filter(s => s.name.toLowerCase().includes(q));
        }
     }">
    <div class="pointer-events-none fixed inset-0 opacity-70">
        <div class="absolute -left-24 top-20 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>
        <div class="absolute right-0 top-1/3 h-96 w-96 rounded-full bg-violet-500/10 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-[800px] px-4 py-6 sm:px-6 lg:px-8">

        <a href="{{ route('subject-teacher.results.index', ['subject_offering_id' => $offering->id, 'academic_session_id' => $offering->academic_session_id, 'term_id' => $offering->term_id]) }}"
           class="mb-4 inline-flex items-center gap-1 text-sm text-slate-400 hover:text-white">
            &larr; Back to summary
        </a>

        <div class="cx-panel mb-6 overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-6 shadow-2xl backdrop-blur-2xl">
            <h1 class="text-2xl font-black tracking-tight">{{ $offering->subject->name }} — {{ $offering->schoolClass->name }}{{ $offering->arm ? ' '.$offering->arm->name : '' }}</h1>
            <p class="mt-1 text-sm text-slate-400">{{ $students->count() }} students registered. Click a student to enter their score.</p>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-300">{{ session('success') }}</div>
        @endif

        <div class="cx-panel mb-4 rounded-[1.5rem] border border-white/10 bg-slate-900/70 p-3">
            <input type="text" x-model="search" placeholder="Search students…"
                   class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm">
        </div>

        <div class="cx-panel overflow-hidden rounded-[1.75rem] border border-white/10 bg-slate-900/75 shadow-2xl divide-y divide-white/5">
            <template x-for="student in filtered" :key="student.student_id">
                <a :href="'{{ url('/subject-teacher/results/'.$offering->id.'/students') }}/' + student.student_id"
                   class="flex items-center justify-between px-5 py-4 hover:bg-white/5 transition">
                    <span class="font-medium" x-text="student.name"></span>
                    <span class="flex items-center gap-3">
                        <template x-if="student.entered">
                            <span class="rounded-full bg-emerald-400/10 border border-emerald-400/20 px-3 py-1 text-xs font-bold text-emerald-300" x-text="'Score: ' + student.total_score"></span>
                        </template>
                        <template x-if="!student.entered">
                            <span class="rounded-full bg-amber-400/10 border border-amber-400/20 px-3 py-1 text-xs font-bold text-amber-300">Missing</span>
                        </template>
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                </a>
            </template>
            <template x-if="filtered.length === 0">
                <p class="px-5 py-10 text-center text-sm text-slate-500">No students match your search.</p>
            </template>
        </div>
    </div>
</div>
</x-app-layout>
