@props(['type' => 'button', 'loadingText' => null])

@if ($type === 'submit')
    <button
        type="submit"
        x-data="{ submitting: false }"
        x-init="$el.closest('form')?.addEventListener('submit', () => { submitting = true })"
        :disabled="submitting"
        {{ $attributes->merge(['class' => 'btn-brand-primary px-4 py-2 rounded-md text-sm font-medium inline-flex items-center gap-2']) }}
    >
        <svg x-show="submitting" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        <span x-show="!submitting">{{ $slot }}</span>
        @if ($loadingText)
            <span x-show="submitting" x-cloak>{{ $loadingText }}</span>
        @endif
    </button>
@else
    <button type="button" {{ $attributes->merge(['class' => 'btn-brand-primary px-4 py-2 rounded-md text-sm font-medium']) }}>
        {{ $slot }}
    </button>
@endif
