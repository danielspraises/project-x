@extends('layouts.app')

@section('content')
<div class="cx-stage min-h-screen px-4 py-8 md:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="cx-panel cx-glow rounded-[2rem] p-6 md:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.28em] opacity-60">
                Result Engine / Tertiary
            </p>
            <h1 class="mt-2 text-3xl font-black md:text-5xl">Transcript Studio</h1>
            <p class="mt-3 max-w-2xl text-sm opacity-65">
                Generate cumulative academic transcripts from finalized tertiary results.
            </p>

            <div class="cx-card mt-8 rounded-[1.5rem] p-5">
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($students as $student)
                        <a
                            href="{{ route('ict-admin.results.transcripts.show', $student) }}"
                            class="cx-card rounded-2xl p-5 transition hover:-translate-y-1"
                            data-cx-tilt
                        >
                            <div class="text-xs uppercase tracking-wider opacity-50">
                                {{ $student->matric_number ?? $student->admission_number ?? 'Student' }}
                            </div>
                            <div class="mt-2 font-bold">{{ $student->fullName() }}</div>
                            <div class="mt-3 text-xs opacity-50">Open transcript →</div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
