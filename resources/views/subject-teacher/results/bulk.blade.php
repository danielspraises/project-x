<x-app-layout>
<div class="cx-stage min-h-screen overflow-hidden bg-slate-950 text-slate-100">
    <div class="pointer-events-none fixed inset-0 opacity-70">
        <div class="absolute -left-24 top-20 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>
        <div class="absolute right-0 top-1/3 h-96 w-96 rounded-full bg-violet-500/10 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-[1000px] px-4 py-6 sm:px-6 lg:px-8">

        <a href="{{ route('subject-teacher.results.index', ['subject_offering_id' => $offering->id, 'academic_session_id' => $selectedSession, 'term_id' => $selectedTerm]) }}"
           class="mb-4 inline-flex items-center gap-1 text-sm text-slate-400 hover:text-white">
            &larr; Back to summary
        </a>

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-300">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-300">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ url('/subject-teacher/results/'.$offering->id.'/save') }}">
            @csrf
            <input type="hidden" name="academic_session_id" value="{{ $selectedSession }}">
            <input type="hidden" name="term_id" value="{{ $selectedTerm }}">

            <div class="cx-panel overflow-hidden rounded-[1.75rem] border border-white/10 bg-slate-900/75 shadow-2xl">
                <div class="flex flex-col gap-3 border-b border-white/10 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-lg font-black">{{ $offering->subject->name }} — {{ $offering->schoolClass->name }}{{ $offering->arm ? ' '.$offering->arm->name : '' }}</h1>
                        <p class="text-xs text-slate-500">Bulk entry grid — {{ $students->count() }} students</p>
                    </div>
                    <button class="rounded-xl bg-cyan-400 px-5 py-2 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20">Save all scores</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[700px] w-full text-left text-sm">
                        <thead class="bg-white/[0.03] text-[10px] uppercase tracking-[.16em] text-slate-500">
                            <tr><th class="px-5 py-3">Student</th><th class="px-5 py-3">CA Score</th><th class="px-5 py-3">Exam Score</th><th class="px-5 py-3">Total</th></tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                        @forelse($students as $i => $student)
                            @php $existing = $existingResults->get($student->id); @endphp
                            <tr>
                                <td class="px-5 py-3">
                                    <input type="hidden" name="scores[{{ $i }}][student_id]" value="{{ $student->id }}">
                                    {{ $student->last_name }}, {{ $student->first_name }}
                                </td>
                                <td class="px-5 py-3">
                                    <input type="number" step="0.01" min="0" max="100" name="scores[{{ $i }}][ca_score]" value="{{ $existing?->ca_score }}"
                                        class="w-24 rounded-lg border border-white/10 bg-white/5 px-2 py-1 text-sm">
                                </td>
                                <td class="px-5 py-3">
                                    <input type="number" step="0.01" min="0" max="100" name="scores[{{ $i }}][exam_score]" value="{{ $existing?->exam_score }}"
                                        class="w-24 rounded-lg border border-white/10 bg-white/5 px-2 py-1 text-sm">
                                </td>
                                <td class="px-5 py-3 text-slate-400">{{ $existing?->total_score ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">No students registered for this subject.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
