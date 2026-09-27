<x-app-layout>
<x-slot name="header"><div><div class="cx-kicker">Academic command center</div><div class="font-display text-base font-semibold text-white">Subjects</div></div></x-slot>
<div class="px-4 py-7 sm:px-6 lg:px-8">
<div class="mx-auto max-w-[1500px] space-y-8">
@if(session('success'))<div class="cx-reveal rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-5 py-4 text-sm text-emerald-200">{{ session('success')}}</div>@endif
<section class="cx-stage cx-reveal" data-cx-tilt>
<div class="cx-panel cx-grid cx-glow relative overflow-hidden rounded-[32px] p-7 sm:p-10 lg:p-12">
<div class="absolute right-8 top-8 h-28 w-28 rounded-full border border-white/10"></div><div class="absolute right-16 top-16 h-12 w-12 rounded-full border border-white/10"></div>
<div class="max-w-3xl"><div class="cx-kicker">Class-first architecture</div><h1 class="cx-title mt-4">Design the academic layer<br><span style="color:color-mix(in srgb,var(--brand-primary) 78%,white)">with cinematic clarity.</span></h1><p class="cx-subtitle mt-5 max-w-2xl">Select a class, then orchestrate subjects, teaching scope, teachers and student participation without mixing catalogue definitions with period-specific offerings.</p>
<div class="mt-7 flex flex-wrap gap-3"><a href="{{ route('ict-admin.subjects.create') }}" class="cx-button cx-button-primary rounded-2xl px-5 py-3 text-sm font-bold">Add Subject</a><a href="{{ route('ict-admin.subject-offerings.index') }}" class="cx-button rounded-2xl px-5 py-3 text-sm font-bold">Subject Offerings</a><button type="button" @click="$dispatch('open-subject-guide')" class="cx-button rounded-2xl px-5 py-3 text-sm font-bold">How it works</button></div></div>
<div class="mt-10 grid max-w-2xl grid-cols-3 gap-5"><div class="cx-stat"><div class="text-2xl font-display font-bold">01</div><div class="mt-1 text-xs text-slate-500">Catalogue</div></div><div class="cx-stat"><div class="text-2xl font-display font-bold">02</div><div class="mt-1 text-xs text-slate-500">Offering</div></div><div class="cx-stat"><div class="text-2xl font-display font-bold">03</div><div class="mt-1 text-xs text-slate-500">Enrollment</div></div></div>
</div></section>
<section><div class="mb-5 flex items-end justify-between"><div><div class="cx-kicker">Academic structure</div><h2 class="mt-2 font-display text-2xl font-semibold">Choose a class</h2><p class="mt-1 text-sm text-slate-500">{{ $classes->count() }} available class{{ $classes->count()===1?'':'es' }}</p></div></div>
<div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
@forelse($classes as $class)
<a href="{{ route('ict-admin.subject-offerings.class',$class) }}" class="cx-reveal cx-card cx-panel group rounded-[28px] p-6" data-cx-tilt><div class="flex items-start justify-between"><div class="cx-icon-orb">{{ strtoupper(substr($class->name,0,1)) }}</div><span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $class->arms->count() }} arm{{ $class->arms->count()===1?'':'s' }}</span></div><div class="mt-8"><div class="cx-kicker">Class environment</div><h3 class="mt-2 font-display text-xl font-semibold">{{ $class->name }}</h3><p class="mt-2 text-sm leading-6 text-slate-500">Configure the subjects, teachers and enrollment rules for this class.</p></div><div class="mt-7 flex items-center justify-between border-t border-white/10 pt-4"><span class="text-[11px] font-bold uppercase tracking-[.16em] text-slate-500">Enter setup</span><span class="text-lg text-slate-300 transition group-hover:translate-x-1" aria-hidden="true">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
    </svg>
</span></div></a>
@empty<div class="cx-panel rounded-[28px] p-12 text-center text-sm text-slate-500 sm:col-span-2 xl:col-span-3">No classes yet.</div>@endforelse
</div></section>
</div></div>
<div x-data="{open:false}" @open-subject-guide.window="open=true" x-show="open" x-cloak class="cx-modal fixed inset-0 z-[90] grid place-items-center p-4" @click.self="open=false"><div class="cx-panel-solid w-full max-w-xl rounded-[30px] p-7" x-transition.scale><div class="flex items-start justify-between"><div><div class="cx-kicker">System model</div><h3 class="mt-2 font-display text-2xl font-semibold">Three layers. One flow.</h3></div><button type="button" class="cx-button rounded-xl px-3 py-2" @click="open=false" aria-label="Close">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
    </svg>
</button></div><div class="mt-7 space-y-3"><div class="rounded-2xl border border-white/8 bg-white/[.035] p-4"><b>Catalogue</b><p class="mt-1 text-sm text-slate-500">Reusable institution subject definition.</p></div><div class="rounded-2xl border border-white/8 bg-white/[.035] p-4"><b>Offering</b><p class="mt-1 text-sm text-slate-500">Subject + class + session + period + teaching scope.</p></div><div class="rounded-2xl border border-white/8 bg-white/[.035] p-4"><b>Registration</b><p class="mt-1 text-sm text-slate-500">The actual students participating in the offering.</p></div></div></div></div>
</x-app-layout>
