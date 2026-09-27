<x-app-layout>
<x-slot name="header"><div><div class="cx-kicker">Account</div><div class="font-display text-base font-semibold text-[var(--ink)]">{{ __('Profile') }}</div></div></x-slot>

<div class="profile-shell relative px-4 py-8 sm:px-6 lg:px-8">
<div class="mx-auto max-w-[1100px] space-y-7">
<section class="cx-panel cx-grid rounded-[30px] p-7 sm:p-9">
<div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
<div><div class="cx-kicker">Personal workspace</div><h1 class="mt-2 font-display text-3xl font-semibold">Your profile</h1><p class="mt-2 max-w-2xl text-sm leading-7 text-[var(--muted)]">Manage your personal information, account security and access credentials.</p></div>
<div class="h-16 w-16 shrink-0 rounded-[22px] grid place-items-center text-xl font-bold text-white shadow-xl" style="background:linear-gradient(145deg,var(--brand-primary),var(--brand-secondary))">{{ collect(explode(' ', $user->name))->map(fn($p)=>strtoupper($p[0]??''))->take(2)->implode('') }}</div>
</div>
</section>

<div class="ui-profile-card p-6 sm:p-8">
@include('profile.partials.update-profile-information-form')
</div>
<div class="ui-profile-card p-6 sm:p-8">
@include('profile.partials.update-password-form')
</div>
<div class="ui-profile-card p-6 sm:p-8">
@include('profile.partials.delete-user-form')
</div>
</div>
</div>
</x-app-layout>