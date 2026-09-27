<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="cx-kicker">Academic structure</div>
            <div class="font-display text-base font-semibold">Add Subject Offering</div>
        </div>
    </x-slot>

    <div class="px-4 py-7 sm:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-5xl">
            @include('ict-admin.subject-offerings.form', [
                'action' => route('ict-admin.subject-offerings.store'),
                'method' => 'POST',
            ])
        </div>
    </div>
</x-app-layout>
