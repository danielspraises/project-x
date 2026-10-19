<x-app-layout>
@php
    $canAct = $submission->status === 'submitted';
    $statusClass = match ($submission->status) {
        'returned' => 'border-rose-400/20 bg-rose-400/10 text-rose-300',
        'submitted' => 'border-amber-400/20 bg-amber-400/10 text-amber-300',
        default => 'border-emerald-400/20 bg-emerald-400/10 text-emerald-300',
    };
@endphp
<div class="cx-stage min-h-screen overflow-hidden bg-slate-950 text-slate-100">
    <div class="pointer-events-none fixed inset-0 opacity-70">
        <div class="absolute -left-24 top-20 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>
        <div class="absolute right-0 top-1/3 h-96 w-96 rounded-full bg-violet-500/10 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-[1300px] px-4 py-6 sm:px-6 lg:px-8">

        <a href="{{ $backUrl }}" class="mb-4 inline-flex items-center gap-1 text-sm text-slate-400 hover:text-white">&larr; Back to review queue</a>

        <div class="cx-panel mb-6 overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-6 shadow-2xl backdrop-blur-2xl">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="text-xs uppercase tracking-[.18em] text-slate-500">Review submission</div>
                    <h1 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl">{{ $title }}</h1>
                    @if ($subtitle)
                        <p class="mt-1 text-sm text-slate-400">{{ $subtitle }}</p>
                    @endif
                </div>
                <span class="rounded-full border px-3 py-1 text-xs font-bold uppercase {{ $statusClass }}">{{ $submission->status }}</span>
            </div>

            <div class="mt-4 flex flex-wrap gap-x-6 gap-y-1 text-xs text-slate-400">
                <span>Submitted by <span class="font-semibold text-slate-300">{{ $submission->submittedBy?->name ?? '—' }}</span></span>
                @if ($submission->submitted_at)
                    <span>on <span class="font-semibold text-slate-300">{{ $submission->submitted_at->format('d M Y, H:i') }}</span></span>
                @endif
                @if ($scheme)
                    <span>Scheme:
                        <span class="font-semibold text-slate-300">
                            {{ $components->where('type', '!=', 'exam')->map(fn ($c) => $c->name.' ('.$c->max_score.')')->implode(' + ') ?: 'no CA' }}
                            · Exam {{ $scheme->exam_max }}
                        </span>
                    </span>
                @endif
            </div>

            @if ($submission->submission_note)
                <div class="mt-4 rounded-xl border border-white/10 bg-white/[0.04] px-4 py-3 text-sm text-slate-300">
                    <span class="font-bold text-slate-200">Note from submitter:</span> {{ $submission->submission_note }}
                </div>
            @endif
        </div>

        @if ($submission->verifications->isNotEmpty())
            <div class="cx-panel mb-6 rounded-[1.5rem] border border-white/10 bg-slate-900/75 p-5">
                <h2 class="text-sm font-black">Earlier reviews</h2>
                <div class="mt-3 space-y-2">
                    @foreach ($submission->verifications->sortByDesc('reviewed_at') as $verification)
                        <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                            <span class="font-bold {{ $verification->decision === 'returned' ? 'text-rose-300' : 'text-emerald-300' }}">{{ ucfirst($verification->decision) }}</span>
                            <span class="text-slate-500">by {{ $verification->reviewer?->name ?? '—' }}{{ $verification->reviewed_at ? ' · '.$verification->reviewed_at->format('d M Y, H:i') : '' }}</span>
                            @if ($verification->notes)
                                <div class="mt-1 text-slate-300">{{ $verification->notes }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-white/10 bg-white/5 p-4 text-center">
                <div class="text-2xl font-black">{{ $stats['count'] }}</div>
                <div class="text-xs text-slate-500">Students</div>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/5 p-4 text-center">
                <div class="text-2xl font-black">{{ $stats['average'] ?? '—' }}</div>
                <div class="text-xs text-slate-500">Average total</div>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/5 p-4 text-center">
                <div class="text-2xl font-black">{{ $stats['highest'] ?? '—' }}</div>
                <div class="text-xs text-slate-500">Highest</div>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/5 p-4 text-center">
                <div class="text-2xl font-black">{{ $stats['lowest'] ?? '—' }}</div>
                <div class="text-xs text-slate-500">Lowest</div>
            </div>
        </div>

        <div class="mb-4 flex flex-wrap items-center gap-2 text-xs">
            @foreach ($stats['grades'] as $grade => $count)
                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 font-bold text-slate-300">{{ $grade }}: {{ $count }}</span>
            @endforeach
            @if ($stats['incomplete'] > 0)
                <span class="rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 font-bold text-amber-300">{{ $stats['incomplete'] }} without a total</span>
            @endif
            <span class="text-slate-500">A = absent (counts as 0)</span>
        </div>

        <div class="cx-panel mb-6 overflow-hidden rounded-[1.75rem] border border-white/10 bg-slate-900/75 shadow-2xl">
            <div class="overflow-x-auto">
                <table class="min-w-[700px] w-full text-left text-sm">
                    <thead class="bg-white/[0.03] text-[10px] uppercase tracking-[.16em] text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Student</th>
                            @foreach ($components as $component)
                                <th class="px-3 py-3">{{ $component->name }} <span class="text-slate-600 normal-case">/ {{ $component->max_score }}</span></th>
                            @endforeach
                            <th class="px-3 py-3">CA</th>
                            <th class="px-3 py-3">Exam</th>
                            <th class="px-3 py-3">Total</th>
                            <th class="px-5 py-3">Grade</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="px-5 py-3">
                                    {{ $row['name'] }}
                                    @if ($row['identifier'])
                                        <div class="text-xs text-slate-500">{{ $row['identifier'] }}</div>
                                    @endif
                                </td>
                                @foreach ($components as $component)
                                    @php $cell = $row['cells'][$component->id] ?? ''; @endphp
                                    <td class="px-3 py-3 {{ $cell === 'A' ? 'text-amber-300' : 'text-slate-300' }}">{{ $cell !== '' ? $cell : '—' }}</td>
                                @endforeach
                                <td class="px-3 py-3 text-slate-300">{{ $row['ca'] ?? '—' }}</td>
                                <td class="px-3 py-3 text-slate-300">{{ $row['exam'] ?? '—' }}</td>
                                <td class="px-3 py-3 font-bold">{{ $row['total'] ?? '—' }}</td>
                                <td class="px-5 py-3 font-bold text-cyan-300">{{ $row['grade'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $components->count() + 5 }}" class="px-5 py-10 text-center text-slate-500">No scores are attached to this submission.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($canAct)
            <div class="cx-panel rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5" x-data="reviewActions(@js($verifyUrl), @js($backUrl))">
                <h2 class="font-black">Your decision</h2>
                <p class="mt-1 text-xs text-slate-500">Approve to move this forward, or return it with a note explaining what needs correcting.</p>
                <textarea x-model="notes" rows="3" placeholder="Note (required if returning)"
                          class="mt-3 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm outline-none focus:border-cyan-400/50"></textarea>

                <p x-show="message" x-text="message" x-cloak class="mt-2 text-sm" :class="error ? 'text-rose-300' : 'text-emerald-300'"></p>

                <div class="mt-3 flex justify-end gap-2">
                    <button type="button" @click="decide('returned')" :disabled="busy"
                            class="rounded-xl border border-rose-400/30 bg-rose-400/10 px-5 py-2.5 text-sm font-black text-rose-300 disabled:opacity-40">Return</button>
                    <button type="button" @click="decide('approved')" :disabled="busy"
                            class="rounded-xl bg-emerald-400 px-5 py-2.5 text-sm font-black text-slate-950 disabled:opacity-40">Approve</button>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-white/10 bg-white/[0.04] px-4 py-3 text-sm text-slate-400">
                This submission has already been reviewed, so there is nothing left to decide here.
            </div>
        @endif
    </div>
</div>

<script>
function reviewActions(verifyUrl, backUrl) {
    return {
        notes: '',
        busy: false,
        message: '',
        error: false,

        async decide(decision) {
            if (decision === 'returned' && !this.notes.trim()) {
                this.error = true;
                this.message = 'Please add a note explaining why this is being returned.';
                return;
            }

            this.busy = true;
            this.message = '';

            try {
                const response = await fetch(verifyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ decision, notes: this.notes || null }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    this.error = true;
                    this.message = data.message || 'Something went wrong. Please try again.';
                    this.busy = false;
                    return;
                }

                this.error = false;
                this.message = data.message || 'Saved.';
                setTimeout(() => { window.location = backUrl; }, 900);
            } catch (e) {
                this.error = true;
                this.message = 'Network error — please check your connection and try again.';
                this.busy = false;
            }
        },
    };
}
</script>
</x-app-layout>
