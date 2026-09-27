<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="cx-kicker">Academic structure</div>
            <div class="font-display text-base font-semibold">Subject Offerings</div>
        </div>
    </x-slot>

    <style>
        .subject-offerings-page { --so-primary: var(--brand-primary); --so-secondary: var(--brand-secondary); }
        .so-filter {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--panel);
            box-shadow: 0 8px 18px rgba(15,23,42,.06), 0 2px 4px rgba(15,23,42,.04);
        }
        .so-filter-field { flex: 1 1 0; min-width: 0; }
        .so-filter-actions { display: flex; gap: 8px; flex: 0 0 auto; }
        .so-offering-card {
            position: relative;
            overflow: hidden;
            border: 1px solid color-mix(in srgb, var(--so-primary) 18%, var(--line));
            border-radius: 18px;
            background: color-mix(in srgb, var(--so-primary) 5%, var(--panel));
            box-shadow: 0 18px 34px rgba(15,23,42,.10), 0 5px 10px rgba(15,23,42,.06), inset 0 1px 0 rgba(255,255,255,.55);
        }
        .so-card-inner { padding: 18px; }
        .so-subject {
            display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
            padding: 12px 14px;
            border-radius: 13px;
            background: color-mix(in srgb, var(--so-primary) 11%, var(--panel));
        }
        .so-batch {
            margin-top: 12px;
            padding: 14px;
            border: 1px solid color-mix(in srgb, var(--so-secondary) 25%, var(--line));
            border-radius: 14px;
            background: color-mix(in srgb, var(--so-secondary) 12%, var(--panel));
        }
        .so-team-chip {
            border: 1px solid color-mix(in srgb, var(--so-primary) 18%, var(--line));
            border-radius: 9px;
            padding: 6px 9px;
            background: color-mix(in srgb, var(--so-primary) 8%, var(--panel));
            font-size: 12px;
        }
        .so-actions { border-top: 1px solid var(--line); margin-top: 14px; padding-top: 12px; }
        @media (max-width: 900px) {
            .so-filter { align-items: stretch; flex-wrap: wrap; }
            .so-filter-field { flex: 1 1 calc(33.333% - 12px); }
        }
        @media (max-width: 640px) {
            .so-filter-field { flex: 1 1 100%; }
            .so-filter-actions { width: 100%; }
            .so-filter-actions > * { flex: 1; }
        }
    </style>

    <div class="subject-offerings-page px-4 py-7 sm:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-6xl space-y-6">
            @if (session('success'))
                <div class="rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-700 dark:text-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="font-display text-2xl font-semibold tracking-tight">Subject Offerings</h1>
                    <p class="mt-1 text-sm text-slate-500">Manage assigned subjects and teaching teams.</p>
                </div>
                <a href="{{ route('ict-admin.subject-offerings.create') }}"
                   class="btn-brand-primary inline-flex w-fit items-center rounded-xl px-4 py-3 text-sm font-semibold">
                    + Add Offering
                </a>
            </div>

            <form method="GET" class="so-filter">
                <div class="so-filter-field">
                    <label for="academic_session_id" class="ui-label mb-1.5">Academic session</label>
                    <select id="academic_session_id" name="academic_session_id" class="cx-input w-full rounded-xl">
                        <option value="">All sessions</option>
                        @foreach ($sessions as $session)
                            <option value="{{ $session->id }}" @selected($sessionId == $session->id)>{{ $session->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="so-filter-field">
                    <label for="term_id" class="ui-label mb-1.5">Period</label>
                    <select id="term_id" name="term_id" class="cx-input w-full rounded-xl">
                        <option value="">All periods</option>
                        @foreach ($sessions as $session)
                            @foreach ($session->terms as $term)
                                <option value="{{ $term->id }}" @selected($termId == $term->id)>{{ $session->name }} — {{ $term->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>

                <div class="so-filter-field">
                    <label for="class_id" class="ui-label mb-1.5">Class</label>
                    <select id="class_id" name="class_id" class="cx-input w-full rounded-xl">
                        <option value="">All classes</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" @selected($classId == $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="so-filter-actions">
                    <a href="{{ route('ict-admin.subject-offerings.index') }}" class="cx-button inline-flex items-center justify-center rounded-xl px-4 py-3 text-sm font-medium">Clear</a>
                    <button type="submit" class="btn-brand-primary inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-semibold">Filter</button>
                </div>
            </form>

            <div>
                <div class="mb-4 flex items-end justify-between px-1">
                    <div>
                        <div class="cx-kicker">Current records</div>
                        <div class="mt-1 font-display text-lg font-semibold">{{ $offerings->count() }} offering{{ $offerings->count() === 1 ? '' : 's' }}</div>
                    </div>
                </div>

                @if ($offerings->isNotEmpty())
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($offerings as $offering)
                            @php($teachers = $offering->teachers->isNotEmpty() ? $offering->teachers : $offering->effectiveTeachers())
                            <article class="so-offering-card">
                                <div class="so-card-inner">
                                    <div class="so-subject">
                                        <div class="min-w-0">
                                            <div class="font-display text-lg font-semibold leading-tight">{{ $offering->subject?->name ?? '—' }}</div>
                                            @if ($offering->subject?->code)
                                                <div class="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500">{{ $offering->subject->code }}</div>
                                            @endif
                                        </div>
                                        <span class="shrink-0 rounded-full border border-[var(--line)] bg-[var(--panel)] px-2.5 py-1 text-xs font-semibold">
                                            {{ $offering->scope === 'class' ? 'Whole class' : 'Specific arm' }}
                                        </span>
                                    </div>

                                    <div class="so-batch">
                                        <div class="text-[10px] font-semibold uppercase tracking-[0.12em]" style="color: var(--so-secondary)">Class batch</div>
                                        <div class="mt-1 text-sm font-semibold">
                                            {{ $offering->schoolClass?->name ?? '—' }}
                                            @if ($offering->scope === 'arm' && $offering->arm)
                                                <span class="font-medium opacity-70">· {{ $offering->arm->name }}</span>
                                            @endif
                                        </div>
                                        <div class="mt-2 text-xs text-slate-500">
                                            {{ $offering->term?->name ?? '—' }} · {{ $offering->academicSession?->name ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">Teaching team</div>
                                        @if ($teachers->isNotEmpty())
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach ($teachers as $teacher)
                                                    <span class="so-team-chip">{{ $teacher->name }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="mt-2 text-sm text-slate-500">No teacher assigned</div>
                                        @endif
                                    </div>

                                    <div class="so-actions flex items-center justify-end gap-3">
                                        <a href="{{ route('ict-admin.subject-offerings.edit', $offering) }}" class="text-sm font-semibold" style="color: var(--so-primary)">Edit</a>
                                        <form method="POST" action="{{ route('ict-admin.subject-offerings.destroy', $offering) }}" onsubmit="return confirm('Delete this subject offering?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-semibold text-rose-600 dark:text-rose-300">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="cx-panel rounded-2xl border border-[var(--line)] bg-[var(--panel)] px-5 py-12 text-center shadow-sm">
                        <div class="font-display text-lg font-semibold">No subject offerings found</div>
                        <p class="mt-1.5 text-sm text-slate-500">Create an offering to assign a subject to a class or arm.</p>
                        <a href="{{ route('ict-admin.subject-offerings.create') }}" class="btn-brand-primary mt-5 inline-flex rounded-xl px-4 py-2.5 text-sm font-semibold">Add Offering</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
