<x-app-layout>
<div class="cx-stage min-h-screen overflow-hidden bg-slate-950 text-slate-100">
    <div class="pointer-events-none fixed inset-0 opacity-70">
        <div class="absolute -left-24 top-20 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>
        <div class="absolute right-0 top-1/3 h-96 w-96 rounded-full bg-violet-500/10 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-[1000px] px-4 py-6 sm:px-6 lg:px-8">

        <div class="cx-panel mb-6 overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-6 shadow-2xl backdrop-blur-2xl">
            <h1 class="text-3xl font-black tracking-tight sm:text-4xl">Result Entry</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-400">Pick a subject to see its progress, then enter scores one student at a time or with the bulk grid.</p>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-300">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-300">{{ session('error') }}</div>
        @endif

        <form method="GET" action="{{ url('/subject-teacher/results') }}" class="cx-panel mb-6 grid gap-3 rounded-[1.75rem] border border-white/10 bg-slate-900/70 p-4 shadow-xl backdrop-blur-xl md:grid-cols-3">
            <select name="academic_session_id" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm" onchange="this.form.submit()">
                @foreach($sessions as $session)
                    <option value="{{ $session->id }}" @selected($selectedSession == $session->id)>{{ $session->name }}</option>
                @endforeach
            </select>
            <select name="term_id" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm" onchange="this.form.submit()">
                @foreach($terms as $term)
                    <option value="{{ $term->id }}" @selected($selectedTerm == $term->id)>{{ $term->name }}</option>
                @endforeach
            </select>
            <select name="subject_offering_id" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm" onchange="this.form.submit()">
                <option value="">Select a subject…</option>
                @foreach($offerings as $offering)
                    <option value="{{ $offering->id }}" @selected($selectedOffering?->id === $offering->id)>
                        {{ $offering->subject->name }} — {{ $offering->schoolClass->name }}{{ $offering->arm ? ' '.$offering->arm->name : '' }}
                    </option>
                @endforeach
            </select>
        </form>

        @if($selectedOffering && $stats)
            <div class="cx-panel mb-6 rounded-[1.75rem] border border-white/10 bg-slate-900/75 p-6 shadow-2xl">
                <h2 class="text-lg font-black">{{ $selectedOffering->subject->name }} — {{ $selectedOffering->schoolClass->name }}{{ $selectedOffering->arm ? ' '.$selectedOffering->arm->name : '' }}</h2>

                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div class="rounded-xl border border-white/10 bg-white/5 p-4 text-center">
                        <div class="text-2xl font-black">{{ $stats['total'] }}</div>
                        <div class="text-xs text-slate-500">Registered</div>
                    </div>
                    <div class="rounded-xl border border-emerald-400/20 bg-emerald-400/5 p-4 text-center">
                        <div class="text-2xl font-black text-emerald-300">{{ $stats['entered'] }}</div>
                        <div class="text-xs text-slate-500">Scored</div>
                    </div>
                    <div class="rounded-xl border border-amber-400/20 bg-amber-400/5 p-4 text-center">
                        <div class="text-2xl font-black text-amber-300">{{ $stats['missing'] }}</div>
                        <div class="text-xs text-slate-500">Missing</div>
                    </div>
                </div>

                @if($scheme)
                    <div class="mt-4 rounded-xl border border-white/10 bg-white/[0.04] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-xs uppercase tracking-[.18em] text-slate-500">Assessment scheme</div>
                            @if($scheme->isLocked())
                                <span class="rounded-full bg-slate-500/10 border border-slate-500/20 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-400">Locked</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-slate-300">
                            {{ $scheme->components->where('type', '!=', 'exam')->map(fn($c) => $c->name.' ('.$c->max_score.')')->implode(' + ') }}
                            = {{ $scheme->ca_max }} CA &nbsp;·&nbsp; Exam {{ $scheme->exam_max }}
                        </p>
                        <a href="{{ url('/subject-teacher/results/'.$selectedOffering->id.'/scheme') }}" class="mt-2 inline-block text-xs font-bold text-cyan-300 hover:text-cyan-200">
                            {{ $scheme->isLocked() ? 'View scheme' : 'Edit scheme' }} →
                        </a>
                    </div>
                @endif

                @if($submission)
                    <div class="mt-4 rounded-xl border border-white/10 bg-white/[0.04] p-4">
                        <div class="text-xs uppercase tracking-[.18em] text-slate-500">Submission status</div>
                        <div class="mt-1 text-base font-black uppercase
                            {{ $submission->status === 'returned' ? 'text-rose-300' : ($submission->status === 'submitted' ? 'text-amber-300' : 'text-emerald-300') }}">
                            {{ $submission->status }}
                        </div>
                        @if($submission->status === 'returned' && $submission->verifications->isNotEmpty())
                            @php $latest = $submission->verifications->sortByDesc('reviewed_at')->first(); @endphp
                            <div class="mt-2 rounded-lg border border-rose-400/20 bg-rose-400/5 p-3 text-sm text-rose-200">
                                <span class="font-bold">Reviewer note:</span> {{ $latest->notes ?? 'No note provided.' }}
                            </div>
                        @endif
                    </div>
                @endif

                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ url('/subject-teacher/results/'.$selectedOffering->id.'/students') }}"
                       class="rounded-xl bg-cyan-400 px-5 py-2.5 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20">
                        Enter Scores (Student List)
                    </a>
                    <a href="{{ url('/subject-teacher/results/'.$selectedOffering->id.'/bulk?academic_session_id='.$selectedSession.'&term_id='.$selectedTerm) }}"
                       class="rounded-xl border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-black text-slate-100 hover:bg-white/10">
                        Bulk Entry Grid
                    </a>
                    {{-- CSV upload hidden until it supports per-component columns (Batch 4) --}}
                    {{-- <a href="{{ url('/subject-teacher/results/'.$selectedOffering->id.'/import?academic_session_id='.$selectedSession.'&term_id='.$selectedTerm) }}"
                       class="rounded-xl border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-black text-slate-100 hover:bg-white/10">
                        Bulk Upload (CSV)
                    </a> --}}
                </div>
            </div>

            <form method="POST" action="{{ url('/subject-teacher/results/'.$selectedOffering->id.'/submit') }}" class="cx-panel rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5">
                @csrf
                <h3 class="font-black">Submit for review</h3>
                <p class="mt-1 text-xs text-slate-500">Every registered student needs both a CA and an exam score saved before this will succeed.</p>
                <textarea name="note" rows="2" placeholder="Optional note to the Class Teacher" class="mt-3 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm"></textarea>
                <button class="mt-3 rounded-xl bg-violet-400 px-5 py-2 text-sm font-black text-slate-950">Submit for review</button>
            </form>
        @endif
    </div>
</div>
</x-app-layout>
