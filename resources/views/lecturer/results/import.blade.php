<x-app-layout>
<div class="cx-stage min-h-screen overflow-hidden bg-slate-950 text-slate-100">
    <div class="pointer-events-none fixed inset-0 opacity-70">
        <div class="absolute -left-24 top-20 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>
        <div class="absolute right-0 top-1/3 h-96 w-96 rounded-full bg-violet-500/10 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-[700px] px-4 py-6 sm:px-6 lg:px-8">

        <a href="{{ route('lecturer.results.index', ['course_offering_id' => $offering->id, 'academic_session_id' => $selectedSession, 'term_id' => $selectedTerm]) }}"
           class="mb-4 inline-flex items-center gap-1 text-sm text-slate-400 hover:text-white">
            &larr; Back to summary
        </a>

        <div class="cx-panel mb-6 overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-6 shadow-2xl backdrop-blur-2xl">
            <h1 class="text-2xl font-black tracking-tight">Bulk Upload — {{ $offering->course->code }}</h1>
            <p class="mt-2 text-sm text-slate-400">The template has one column for each part of the assessment scheme. Fill in whichever columns you have — CA only, exam only, or all of them. A <b class="text-slate-200">blank cell leaves that score as it is</b>, so uploading CA scores will never erase exam scores already entered. Type <b class="text-slate-200">A</b> for a student who was absent (counts as 0). Students are matched by matric number. Keep the column headings exactly as downloaded (their order doesn't matter). If you change the assessment scheme, download a fresh template.</p>
        </div>

        <div class="cx-panel mb-6 rounded-[1.5rem] border border-white/10 bg-slate-900/75 p-5">
            <h2 class="font-black">What's in the template</h2>
            <ul class="mt-3 space-y-2 text-sm text-slate-300">
                <li><span class="font-mono text-cyan-300">matric_number</span> <span class="text-slate-500">— identifies the student. Don't edit it.</span></li>
                <li><span class="font-mono text-cyan-300">student_name</span> <span class="text-slate-500">— for your reference only; ignored on upload.</span></li>
                @foreach ($scheme->components as $component)
                    <li>
                        <span class="font-mono text-cyan-300">{{ $component->name }} (max {{ $component->max_score }})</span>
                        <span class="text-slate-500">— a score from 0 to {{ $component->max_score }}, or <b class="text-slate-300">A</b> if absent. Blank keeps the current score.</span>
                    </li>
                @endforeach
            </ul>
        </div>

        @if (session('error'))
            <div class="mb-4 rounded-xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-300">{{ session('error') }}</div>
        @endif

        @if (session('import_summary'))
            @php $summary = session('import_summary'); @endphp
            <div class="cx-panel mb-6 rounded-[1.5rem] border border-white/10 bg-slate-900/75 p-5">
                <h2 class="font-black">Last import</h2>
                <div class="mt-2 flex gap-4 text-sm">
                    <span class="text-emerald-300 font-bold">{{ $summary['updated'] }} updated</span>
                    <span class="text-slate-400">{{ $summary['skipped'] }} skipped</span>
                </div>
                @if (!empty($summary['errors']))
                    <div class="mt-3 max-h-48 overflow-y-auto rounded-lg border border-amber-400/20 bg-amber-400/5 p-3 text-xs text-amber-200 space-y-1">
                        @foreach ($summary['errors'] as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <div class="cx-panel mb-6 rounded-[1.5rem] border border-white/10 bg-slate-900/75 p-5">
            <h2 class="font-black">1. Download the template</h2>
            <p class="mt-1 text-xs text-slate-500">Pre-filled with every registered student and whatever scores are already saved, so you can see at a glance what's missing.</p>
            <a href="{{ route('lecturer.results.import.template', $offering) }}"
               class="mt-3 inline-flex rounded-xl border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-black text-slate-100 hover:bg-white/10">
                Download CSV Template
            </a>
        </div>

        <div class="cx-panel rounded-[1.5rem] border border-white/10 bg-slate-900/75 p-5">
            <h2 class="font-black">2. Upload it back</h2>
            <p class="mt-1 text-xs text-slate-500">Fill in CA, Exam, or both columns — leave the other blank if you don't have it yet.</p>

            <form method="POST" action="{{ route('lecturer.results.import.upload', $offering) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                @csrf
                <input type="hidden" name="academic_session_id" value="{{ $selectedSession }}">
                <input type="hidden" name="term_id" value="{{ $selectedTerm }}">
                <input type="file" name="file" accept=".csv,text/csv" required
                       class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-cyan-400 file:px-4 file:py-2 file:text-sm file:font-bold file:text-slate-950">
                <button class="rounded-xl bg-cyan-400 px-5 py-2.5 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20">Upload &amp; Preview</button>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
