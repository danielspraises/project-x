@extends('layouts.app')

@section('content')
<div class="cx-stage min-h-screen px-4 py-8 md:px-8">
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex items-center justify-between">
            <a href="{{ route('ict-admin.results.transcripts.index') }}" class="text-sm font-semibold opacity-60">
                ← Transcript Studio
            </a>
            <button onclick="window.print()" class="rounded-xl px-5 py-3 text-sm font-bold">
                Print
            </button>
        </div>

        <article class="cx-panel overflow-hidden rounded-[2rem] bg-white p-6 text-slate-900 shadow-2xl md:p-10">
            <header class="border-b border-slate-200 pb-7">
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">
                    {{ $institution->name }}
                </p>
                <h1 class="mt-2 text-3xl font-black">{{ $template->name }}</h1>
                <div class="mt-4 grid gap-2 text-sm md:grid-cols-3">
                    <div><span class="text-slate-500">Student:</span> {{ $student->fullName() }}</div>
                    <div><span class="text-slate-500">Matric:</span> {{ $student->matric_number ?? '—' }}</div>
                    <div><span class="text-slate-500">Programme:</span> {{ $courses->first()['programme']->name ?? '—' }}</div>
                </div>
            </header>

            @foreach($semesters as $semester)
                <section class="mt-8">
                    <div class="mb-3 flex items-end justify-between">
                        <h2 class="text-xl font-black">Academic Period</h2>
                        <div class="text-sm text-slate-500">
                            GPA: <strong class="text-slate-900">{{ number_format($semester['gpa'], 2) }}</strong>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-300 text-xs uppercase tracking-wider text-slate-500">
                                    <th class="px-3 py-3">Code</th>
                                    <th class="px-3 py-3">Course</th>
                                    <th class="px-3 py-3">CU</th>
                                    <th class="px-3 py-3">Score</th>
                                    <th class="px-3 py-3">Grade</th>
                                    <th class="px-3 py-3">Point</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($semester['rows'] as $row)
                                    <tr class="border-b border-slate-100">
                                        <td class="px-3 py-4 font-semibold">{{ $row['course_code'] ?? '—' }}</td>
                                        <td class="px-3 py-4">{{ $row['course_title'] ?? '—' }}</td>
                                        <td class="px-3 py-4">{{ $row['credit_unit'] ?? '—' }}</td>
                                        <td class="px-3 py-4">{{ $row['total_score'] ?? '—' }}</td>
                                        <td class="px-3 py-4">{{ $row['grade'] ?? '—' }}</td>
                                        <td class="px-3 py-4">{{ $row['grade_point'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 text-right text-sm text-slate-500">
                        Credits: {{ number_format($semester['total_credit_units'], 2) }}
                        · Quality points: {{ number_format($semester['total_quality_points'], 2) }}
                    </div>
                </section>
            @endforeach

            <section class="mt-10 grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl bg-slate-100 p-5">
                    <div class="text-xs uppercase tracking-wider text-slate-500">Total credits</div>
                    <div class="mt-1 text-2xl font-black">
                        {{ number_format($cumulative['total_credit_units'], 2) }}
                    </div>
                </div>
                <div class="rounded-2xl bg-slate-100 p-5">
                    <div class="text-xs uppercase tracking-wider text-slate-500">Quality points</div>
                    <div class="mt-1 text-2xl font-black">
                        {{ number_format($cumulative['total_quality_points'], 2) }}
                    </div>
                </div>
                <div class="rounded-2xl bg-slate-900 p-5 text-white">
                    <div class="text-xs uppercase tracking-wider opacity-60">CGPA</div>
                    <div class="mt-1 text-3xl font-black">
                        {{ number_format($cumulative['cgpa'], 2) }}
                    </div>
                </div>
            </section>
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
