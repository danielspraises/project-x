<x-app-layout>
<div class="cx-stage min-h-screen overflow-hidden bg-slate-950 text-slate-100">
    <div class="pointer-events-none fixed inset-0 opacity-70">
        <div class="absolute -left-24 top-20 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>
        <div class="absolute right-0 top-1/3 h-96 w-96 rounded-full bg-violet-500/10 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-[600px] px-4 py-6 sm:px-6 lg:px-8">

        <a href="{{ route('subject-teacher.results.students.index', $offering) }}"
           class="mb-4 inline-flex items-center gap-1 text-sm text-slate-400 hover:text-white">
            &larr; Back to student list
        </a>

        @if (session('error'))
            <div class="mb-4 rounded-xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-300">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-300">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="cx-panel overflow-hidden rounded-[1.75rem] border border-white/10 bg-slate-900/75 p-6 shadow-2xl">
            <div class="text-xs uppercase tracking-[.18em] text-slate-500">{{ $offering->subject->name }}</div>
            <h1 class="mt-1 text-2xl font-black">{{ $student->last_name }}, {{ $student->first_name }}</h1>

            @if ($existing?->total_score !== null)
                <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-emerald-400/10 border border-emerald-400/20 px-3 py-1 text-xs font-bold text-emerald-300">
                    Current total: {{ $existing->total_score }} @if($existing->grade) ({{ $existing->grade }}) @endif
                </div>
            @endif

            <form method="POST" action="{{ route('subject-teacher.results.students.update', [$offering, $student]) }}" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="academic_session_id" value="{{ $offering->academic_session_id }}">
                <input type="hidden" name="term_id" value="{{ $offering->term_id }}">

                @foreach ($scheme->components as $component)
                    @php
                        $cs = $componentScores->get($component->id);
                        $isAbsent = (bool) old('components.'.$component->id.'.is_absent', $cs?->is_absent ?? false);
                        $scoreValue = old('components.'.$component->id.'.score', $cs?->score);
                    @endphp
                    <div x-data="{ absent: {{ $isAbsent ? 'true' : 'false' }} }">
                        <div class="mb-1 flex items-center justify-between">
                            <label class="block text-xs text-slate-400">
                                {{ $component->name }}
                                <span class="text-slate-600">(out of {{ $component->max_score }})</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 text-[11px] text-slate-500">
                                <input type="checkbox" name="components[{{ $component->id }}][is_absent]" value="1" x-model="absent"
                                       class="rounded border-white/20 bg-white/5">
                                Absent
                            </label>
                        </div>
                        <input type="number" step="0.01" min="0" max="{{ $component->max_score }}"
                               name="components[{{ $component->id }}][score]" value="{{ $scoreValue }}"
                               :disabled="absent" :class="absent ? 'opacity-40' : ''"
                               class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                    </div>
                @endforeach

                <div class="flex flex-wrap justify-end gap-3 pt-2">
                    <button type="submit" name="go_to" value="list"
                            class="rounded-xl border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-black text-slate-100 hover:bg-white/10">
                        Save &amp; Back to List
                    </button>
                    @if ($next)
                        <button type="submit" name="go_to" value="next"
                                class="rounded-xl bg-cyan-400 px-5 py-2.5 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20">
                            Save &amp; Next Student
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
