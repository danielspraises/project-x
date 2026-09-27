<x-app-layout>
<div class="cx-stage min-h-screen overflow-hidden bg-slate-950 text-slate-100">
    <div class="pointer-events-none fixed inset-0 opacity-70">
        <div class="absolute -left-24 top-20 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>
        <div class="absolute right-0 top-1/3 h-96 w-96 rounded-full bg-violet-500/10 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-[900px] px-4 py-6 sm:px-6 lg:px-8">

        <div class="cx-panel mb-6 overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-6 shadow-2xl backdrop-blur-2xl">
            <h1 class="text-2xl font-black tracking-tight">Review before saving</h1>
            <p class="mt-2 text-sm text-slate-400">{{ $offering->course->code }} — nothing has been saved yet. Check the rows below, then apply or discard.</p>
        </div>

        @php
            $good = $rows->filter(fn ($r) => empty($r['error']));
            $bad = $rows->filter(fn ($r) => !empty($r['error']));
        @endphp

        <div class="mb-4 flex gap-4 text-sm">
            <span class="rounded-full bg-emerald-400/10 border border-emerald-400/20 px-3 py-1 font-bold text-emerald-300">{{ $good->count() }} ready to apply</span>
            @if ($bad->count())
                <span class="rounded-full bg-rose-400/10 border border-rose-400/20 px-3 py-1 font-bold text-rose-300">{{ $bad->count() }} will be skipped</span>
            @endif
        </div>

        <div class="cx-panel overflow-hidden rounded-[1.75rem] border border-white/10 bg-slate-900/75 shadow-2xl mb-6">
            <div class="overflow-x-auto">
                <table class="min-w-[700px] w-full text-left text-sm">
                    <thead class="bg-white/[0.03] text-[10px] uppercase tracking-[.16em] text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Row</th>
                            <th class="px-5 py-3">Student</th>
                            <th class="px-5 py-3">CA (current → new)</th>
                            <th class="px-5 py-3">Exam (current → new)</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach ($rows as $row)
                            <tr class="{{ !empty($row['error']) ? 'opacity-60' : '' }}">
                                <td class="px-5 py-3 text-slate-500">{{ $row['row'] }}</td>
                                <td class="px-5 py-3">
                                    {{ $row['student_name'] ?? '—' }}
                                    @if (!empty($row['matric_number']))
                                        <div class="text-xs text-slate-500">{{ $row['matric_number'] }}</div>
                                    @endif
                                </td>
                                @if (empty($row['error']))
                                    <td class="px-5 py-3">
                                        {{ $row['current_ca'] ?? '—' }}
                                        @if ((string) $row['current_ca'] !== (string) $row['new_ca'])
                                            <span class="text-cyan-300">→ {{ $row['new_ca'] ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        {{ $row['current_exam'] ?? '—' }}
                                        @if ((string) $row['current_exam'] !== (string) $row['new_exam'])
                                            <span class="text-cyan-300">→ {{ $row['new_exam'] ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="rounded-full bg-emerald-400/10 border border-emerald-400/20 px-2 py-0.5 text-xs font-bold text-emerald-300">Ready</span>
                                    </td>
                                @else
                                    <td class="px-5 py-3 text-slate-600" colspan="2">—</td>
                                    <td class="px-5 py-3">
                                        <span class="rounded-full bg-rose-400/10 border border-rose-400/20 px-2 py-0.5 text-xs font-bold text-rose-300" title="{{ $row['error'] }}">
                                            {{ $row['error'] }}
                                        </span>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-3">
            <form method="POST" action="{{ route('lecturer.results.import.discard', $offering) }}">
                @csrf
                <button class="rounded-xl border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-black text-slate-100 hover:bg-white/10">
                    Discard
                </button>
            </form>
            <form method="POST" action="{{ route('lecturer.results.import.apply', $offering) }}">
                @csrf
                <button class="rounded-xl bg-emerald-400 px-6 py-2.5 text-sm font-black text-slate-950 shadow-lg shadow-emerald-500/20" @disabled($good->isEmpty())>
                    Apply {{ $good->count() }} {{ \Illuminate\Support\Str::plural('Change', $good->count()) }}
                </button>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
