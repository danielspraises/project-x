@extends('layouts.app')

@section('content')
<div class="cx-stage min-h-screen px-4 py-8 md:px-8" x-data>
    <div class="mx-auto max-w-7xl">
        <div class="cx-panel cx-glow overflow-hidden rounded-[2rem] p-6 md:p-8">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] opacity-60">
                        Result Engine / Report Cards
                    </p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight md:text-5xl">
                        Report Card Studio
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm opacity-65">
                        Generate institution-approved student report cards from finalized results.
                    </p>
                </div>

                <div class="cx-card rounded-2xl px-4 py-3 text-sm">
                    <span class="opacity-50">Template</span>
                    <div class="font-bold">
                        {{ optional($institution->reportCardTemplate?->template)->name ?? 'Not assigned' }}
                    </div>
                </div>
            </div>

            <form method="GET" class="cx-card mt-8 rounded-[1.5rem] p-5">
                <div class="grid gap-4 md:grid-cols-3">
                    <label class="block">
                        <span class="mb-2 block text-xs font-semibold uppercase tracking-wider opacity-55">
                            Academic session
                        </span>
                        <select name="academic_session_id" class="w-full rounded-xl border-0 bg-black/20 px-4 py-3">
                            <option value="">Select session</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}">
                                    {{ $session->name ?? $session->title ?? 'Session #'.$session->id }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-xs font-semibold uppercase tracking-wider opacity-55">
                            Term
                        </span>
                        <select name="term_id" class="w-full rounded-xl border-0 bg-black/20 px-4 py-3">
                            <option value="">Select term</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}">
                                    {{ $term->name ?? $term->title ?? 'Term #'.$term->id }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-xs font-semibold uppercase tracking-wider opacity-55">
                            Student
                        </span>
                        <select name="student_id" class="w-full rounded-xl border-0 bg-black/20 px-4 py-3">
                            <option value="">Select student</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">
                                    {{ $student->fullName() }}
                                    @if($student->admission_number)
                                        — {{ $student->admission_number }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <button
                    type="submit"
                    class="mt-5 rounded-xl px-5 py-3 text-sm font-bold shadow-lg transition hover:-translate-y-0.5"
                >
                    Prepare Report Card
                </button>
            </form>

            <div class="mt-8 grid gap-5 md:grid-cols-3">
                <div class="cx-card cx-reveal rounded-[1.5rem] p-6" data-cx-tilt>
                    <div class="text-3xl">◈</div>
                    <h2 class="mt-4 font-bold">Controlled generation</h2>
                    <p class="mt-2 text-sm opacity-60">
                        Generation is available only when Super Admin has activated the feature.
                    </p>
                </div>

                <div class="cx-card cx-reveal rounded-[1.5rem] p-6" data-cx-tilt>
                    <div class="text-3xl">◎</div>
                    <h2 class="mt-4 font-bold">Live positions</h2>
                    <p class="mt-2 text-sm opacity-60">
                        Positions are calculated when the report is generated and are not stored in result rows.
                    </p>
                </div>

                <div class="cx-card cx-reveal rounded-[1.5rem] p-6" data-cx-tilt>
                    <div class="text-3xl">✦</div>
                    <h2 class="mt-4 font-bold">Template driven</h2>
                    <p class="mt-2 text-sm opacity-60">
                        The institution uses the report-card template assigned by Super Admin.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
