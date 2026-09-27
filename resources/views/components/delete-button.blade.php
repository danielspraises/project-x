@props(['action', 'label' => 'Delete', 'confirmTitle' => 'Are you sure?', 'confirmMessage' => 'This action cannot be undone.'])

<div x-data="{ open: false, submitting: false }">
    <button type="button" @click="open = true" class="text-red-600 hover:text-red-800 text-sm">
        {{ $label }}
    </button>

    <!-- Modal -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        style="display: none;"
        @keydown.escape.window="open = false"
    >
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.outside="open = false"
            class="bg-white rounded-lg shadow-xl max-w-sm w-full p-6 text-center"
        >
            <h3 class="text-base font-semibold text-gray-900">{{ $confirmTitle }}</h3>
            <p class="mt-2 text-sm text-gray-600">{{ $confirmMessage }}</p>

            <div class="mt-5 flex justify-center gap-3">
                <button type="button" @click="open = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">
                    Cancel
                </button>
                <form method="POST" action="{{ $action }}" @submit="submitting = true">
                    @csrf
                    @method('DELETE')
                    <button type="submit" :disabled="submitting"
                            class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 disabled:opacity-60 flex items-center gap-2">
                        <svg x-show="submitting" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span x-text="submitting ? 'Deleting...' : '{{ $label }}'"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
