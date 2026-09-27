<x-app-layout>
<div class="cx-stage min-h-screen overflow-hidden bg-slate-950 text-slate-100" x-data="hodApprovals()">
    <div class="pointer-events-none fixed inset-0 opacity-70">
        <div class="absolute -left-24 top-20 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>
        <div class="absolute right-0 top-1/3 h-96 w-96 rounded-full bg-violet-500/10 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-[1400px] px-4 py-6 sm:px-6 lg:px-8">

        <div class="cx-panel mb-6 overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-6 shadow-2xl backdrop-blur-2xl">
            <div class="mb-2 inline-flex items-center gap-2 rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-xs font-semibold text-amber-300">
                <span class="h-2 w-2 animate-pulse rounded-full bg-amber-400"></span> DEPARTMENT REVIEW QUEUE
            </div>
            <h1 class="text-3xl font-black tracking-tight sm:text-4xl">Result Approvals</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-400">Review submissions from lecturers in your department. Approve to forward for ICT review, or return with a note for correction.</p>
        </div>

        <!-- Toast -->
        <div x-show="toast.message" x-transition x-cloak
             class="fixed right-6 top-6 z-50 max-w-sm rounded-xl border px-4 py-3 text-sm shadow-2xl"
             :class="toast.type === 'error' ? 'border-rose-400/30 bg-rose-950/90 text-rose-200' : 'border-emerald-400/30 bg-emerald-950/90 text-emerald-200'">
            <span x-text="toast.message"></span>
        </div>

        <div class="cx-panel overflow-hidden rounded-[1.75rem] border border-white/10 bg-slate-900/75 shadow-2xl">
            <div class="border-b border-white/10 p-5">
                <h2 class="text-lg font-black">Pending submissions</h2>
                <p class="text-xs text-slate-500">Showing submissions awaiting your review. This list updates as you act.</p>
            </div>

            <template x-if="items.length === 0">
                <div class="px-5 py-14 text-center text-sm text-slate-500">No submissions are currently awaiting your review.</div>
            </template>

            <div class="divide-y divide-white/5">
                <template x-for="item in items" :key="item.id">
                    <div class="p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="font-bold" x-text="item.courseLabel"></div>
                                <div class="mt-1 text-xs text-slate-500" x-text="item.programmeLabel"></div>
                                <div class="mt-2 flex flex-wrap gap-3 text-xs text-slate-400">
                                    <span>Submitted by <span class="font-semibold text-slate-300" x-text="item.submittedBy"></span></span>
                                    <span x-show="item.submittedAt">on <span class="font-semibold text-slate-300" x-text="item.submittedAt"></span></span>
                                </div>
                            </div>

                            <div class="w-full lg:w-[420px]">
                                <textarea x-model="item.notes" rows="2" placeholder="Optional note (required if returning)"
                                    class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm outline-none focus:border-cyan-400/50"></textarea>
                                <div class="mt-2 flex justify-end gap-2">
                                    <button type="button" @click="decide(item, 'returned')" :disabled="item.busy"
                                        class="rounded-lg border border-rose-400/30 bg-rose-400/10 px-3 py-2 text-xs font-black text-rose-300 disabled:opacity-40">
                                        Return
                                    </button>
                                    <button type="button" @click="decide(item, 'approved')" :disabled="item.busy"
                                        class="rounded-lg bg-emerald-400 px-3 py-2 text-xs font-black text-slate-950 disabled:opacity-40">
                                        Approve
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>


<script>
function hodApprovals() {
    return {
        items: {!! $submissions->map(function ($s) {
            $course = $s->courseOffering?->course;
            $programme = $s->courseOffering?->programme;
            $submittedBy = $s->submittedBy;

            return [
                'id' => $s->id,
                'courseLabel' => $course?->code
                    ? $course->code . ' — ' . $course->title
                    : 'Result set #' . $s->id,
                'programmeLabel' => $programme?->name ?? '—',
                'submittedBy' => $submittedBy?->name ?? '—',
                'submittedAt' => $s->submitted_at?->format('d M Y, H:i'),
                'notes' => '',
                'busy' => false,
            ];
        })->values()->toJson() !!},

        toast: {
            message: '',
            type: 'success'
        },

        showToast(message, type = 'success') {
            this.toast = {
                message: message,
                type: type
            };

            setTimeout(() => {
                this.toast.message = '';
            }, 4000);
        },

        async decide(item, decision) {
            if (decision === 'returned' && !item.notes.trim()) {
                this.showToast(
                    'Please add a note explaining why this is being returned.',
                    'error'
                );
                return;
            }

            item.busy = true;

            try {
                const csrfToken = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content');

                const response = await fetch(
                    `/hod/results/${item.id}/verify`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            decision: decision,
                            notes: item.notes || null,
                        }),
                    }
                );

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    this.showToast(
                        data.message ||
                        'Something went wrong. Please try again.',
                        'error'
                    );

                    item.busy = false;
                    return;
                }

                this.showToast(
                    data.message || 'Saved successfully.',
                    'success'
                );

                this.items = this.items.filter(
                    currentItem => currentItem.id !== item.id
                );

            } catch (error) {
                console.error(error);

                this.showToast(
                    'Network error — please check your connection and try again.',
                    'error'
                );

                item.busy = false;
            }
        },
    };
}
</script>
</x-app-layout>
