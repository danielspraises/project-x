<x-app-layout>
<div class="cx-stage min-h-screen overflow-hidden bg-slate-950 text-slate-100">
    <div class="pointer-events-none fixed inset-0 opacity-70">
        <div class="absolute -left-24 top-20 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>
        <div class="absolute right-0 top-1/3 h-96 w-96 rounded-full bg-violet-500/10 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-[700px] px-4 py-6 sm:px-6 lg:px-8">

        <a href="{{ route('subject-teacher.results.index', ['subject_offering_id' => $offering->id, 'academic_session_id' => $offering->academic_session_id, 'term_id' => $offering->term_id]) }}"
           class="mb-4 inline-flex items-center gap-1 text-sm text-slate-400 hover:text-white">
            &larr; Back to summary
        </a>

        <div class="cx-panel mb-6 overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-6 shadow-2xl backdrop-blur-2xl">
            <h1 class="text-2xl font-black tracking-tight">Assessment Scheme</h1>
            <p class="mt-1 text-sm text-slate-400">{{ $offering->subject->name }}</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-300 space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if ($scheme->isLocked())
            <div class="cx-panel rounded-[1.5rem] border border-white/10 bg-slate-900/75 p-6">
                <div class="inline-flex items-center gap-2 rounded-full bg-slate-500/10 border border-slate-500/20 px-3 py-1 text-xs font-bold uppercase text-slate-400 mb-4">
                    Locked — scores already exist
                </div>
                <div class="space-y-2">
                    @foreach ($scheme->components->where('type', '!=', 'exam') as $component)
                        <div class="flex justify-between rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                            <span>{{ $component->name }} <span class="text-slate-500">({{ ucfirst($component->type) }})</span></span>
                            <span class="font-bold">{{ $component->max_score }} marks</span>
                        </div>
                    @endforeach
                    <div class="flex justify-between rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <span>Exam</span>
                        <span class="font-bold">{{ $scheme->exam_max }} marks</span>
                    </div>
                </div>
                <p class="mt-4 text-xs text-slate-500">To change this split, ask your ICT Admin to unlock the scheme — scores entered under the current split won't be affected unless it's changed.</p>
            </div>
        @else
            <div class="cx-panel rounded-[1.5rem] border border-white/10 bg-slate-900/75 p-6"
                 x-data="schemeBuilder({
                    initial: {{ $scheme->components->where('type', '!=', 'exam')->values()->map(fn($c) => ['type' => $c->type, 'name' => $c->name, 'max_score' => $c->max_score])->toJson() }},
                    caMax: {{ $scheme->ca_max }},
                 })">
                <p class="text-sm text-slate-400 mb-4">
                    Split the <span class="font-bold text-slate-200">{{ $scheme->ca_max }}</span>-mark continuous assessment share across as many CAs, quizzes, or assignments as you use.
                    The exam stays fixed at <span class="font-bold text-slate-200">{{ $scheme->exam_max }}</span> marks and isn't editable here.
                </p>

                <form method="POST" action="{{ route('subject-teacher.results.scheme.update', $offering) }}">
                    @csrf

                    <div class="space-y-3">
                        <template x-for="(row, index) in rows" :key="index">
                            <div class="flex flex-wrap items-center gap-2 rounded-xl border border-white/10 bg-white/5 p-3">
                                <select :name="'components['+index+'][type]'" x-model="row.type" class="rounded-lg border border-white/10 bg-slate-900/70 px-2 py-2 text-xs">
                                    <option value="ca">CA</option>
                                    <option value="quiz">Quiz</option>
                                    <option value="assignment">Assignment</option>
                                    <option value="attendance">Attendance</option>
                                </select>
                                <input type="text" :name="'components['+index+'][name]'" x-model="row.name" placeholder="e.g. CA 1" required
                                       class="flex-1 min-w-[120px] rounded-lg border border-white/10 bg-slate-900/70 px-3 py-2 text-sm">
                                <input type="number" min="1" max="100" :name="'components['+index+'][max_score]'" x-model.number="row.max_score" required
                                       class="w-24 rounded-lg border border-white/10 bg-slate-900/70 px-3 py-2 text-sm">
                                <button type="button" @click="removeRow(index)" x-show="rows.length > 1" class="text-rose-400 text-lg leading-none px-1">&times;</button>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="addRow()" class="mt-3 text-xs font-bold text-cyan-300 hover:text-cyan-200">+ Add another component</button>

                    <div class="mt-5 rounded-xl border px-4 py-3 text-sm flex justify-between items-center"
                         :class="isValid ? 'border-emerald-400/20 bg-emerald-400/5 text-emerald-300' : 'border-amber-400/20 bg-amber-400/5 text-amber-300'">
                        <span>Running total</span>
                        <span class="font-black" x-text="total + ' / ' + caMax"></span>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="rounded-xl bg-cyan-400 px-6 py-2.5 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20">
                            Save scheme
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>

<script>
function schemeBuilder({ initial, caMax }) {
    return {
        caMax,
        rows: initial.length ? initial : [{ type: 'ca', name: 'CA', max_score: caMax }],
        get total() {
            return this.rows.reduce((sum, r) => sum + Number(r.max_score || 0), 0);
        },
        get isValid() {
            return this.total === this.caMax && this.rows.length > 0;
        },
        addRow() {
            this.rows.push({ type: 'ca', name: '', max_score: 0 });
        },
        removeRow(index) {
            this.rows.splice(index, 1);
        },
    };
}
</script>
</x-app-layout>
