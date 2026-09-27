@extends('layouts.app')

@section('content')
<div class="cx-stage min-h-screen px-4 py-8 md:px-8">
    <div class="mx-auto max-w-5xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ url()->previous() }}" class="text-sm font-semibold opacity-60 hover:opacity-100">
                ← Back
            </a>

            <button
                type="button"
                onclick="window.print()"
                class="rounded-xl px-5 py-3 text-sm font-bold shadow-lg"
            >
                Print
            </button>
        </div>

        <article class="cx-panel overflow-hidden rounded-[2rem] bg-white p-6 text-slate-900 shadow-2xl md:p-10">
            <header class="border-b border-slate-200 pb-7">
                <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">
                            {{ $institution->name }}
                        </p>
                        <h1 class="mt-2 text-3xl font-black">
                            {{ $template->name }}
                        </h1>
                    </div>

                    <div class="rounded-2xl bg-slate-100 px-5 py-4 text-right">
                        <div class="text-xs uppercase tracking-wider text-slate-500">Student</div>
                        <div class="font-bold">{{ $student->fullName() }}</div>
                        <div class="text-sm text-slate-500">
                            {{ $student->admission_number ?? $student->matric_number ?? '' }}
                        </div>
                    </div>
                </div>
            </header>

            <section class="mt-7 grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl bg-slate-100 p-4">
                    <div class="text-xs text-slate-500">Subjects</div>
                    <div class="mt-1 text-2xl font-black">{{ $summary['subjects_count'] }}</div>
                </div>
                <div class="rounded-2xl bg-slate-100 p-4">
                    <div class="text-xs text-slate-500">Total score</div>
                    <div class="mt-1 text-2xl font-black">{{ number_format($summary['total_score'], 2) }}</div>
                </div>
                <div class="rounded-2xl bg-slate-100 p-4">
                    <div class="text-xs text-slate-500">Average</div>
                    <div class="mt-1 text-2xl font-black">{{ number_format($summary['average_score'], 2) }}</div>
                </div>
            </section>

            @if($positions)
                <section class="mt-7 grid gap-4 md:grid-cols-2">
                    @foreach($positions as $label => $position)
                        @if($position)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="text-xs uppercase tracking-wider text-slate-500">
                                    {{ ucfirst($label) }} position
                                </div>
                                <div class="mt-1 text-2xl font-black">{{ $position }}</div>
                            </div>
                        @endif
                    @endforeach
                </section>
            @endif

            <div class="mt-8 overflow-x-auto">
                <table class="w-full min-w-[620px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-300 text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-3 py-3">Subject</th>
                            <th class="px-3 py-3">CA</th>
                            <th class="px-3 py-3">Exam</th>
                            <th class="px-3 py-3">Total</th>
                            @if($settings['show_grade'] ?? true)
                                <th class="px-3 py-3">Grade</th>
                            @endif
                            @if($settings['show_grade_point'] ?? true)
                                <th class="px-3 py-3">Point</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subjects as $row)
                            <tr class="border-b border-slate-100">
                                <td class="px-3 py-4 font-semibold">
                                    {{ $row['subject']->name ?? 'Subject' }}
                                </td>
                                <td class="px-3 py-4">{{ number_format((float) $row['result']->ca_score, 2) }}</td>
                                <td class="px-3 py-4">{{ number_format((float) $row['result']->exam_score, 2) }}</td>
                                <td class="px-3 py-4 font-bold">{{ number_format($row['total'], 2) }}</td>
                                @if($settings['show_grade'] ?? true)
                                    <td class="px-3 py-4">{{ $row['grade'] ?? '—' }}</td>
                                @endif
                                @if($settings['show_grade_point'] ?? true)
                                    <td class="px-3 py-4">{{ $row['grade_point'] ?? '—' }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-8 text-center text-slate-500">
                                    No finalized results are available for this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </div>
</div>

<style>
@media print {
    .cx-stage { padding: 0 !important; }
    article { box-shadow: none !important; border-radius: 0 !important; }
    button, a { display: none !important; }
}
</style>
@endsection
