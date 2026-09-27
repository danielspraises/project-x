@php
    // Any of these session keys, if present, becomes a toast automatically.
    // Views no longer need their own @if(session('success')) blocks — this replaces that pattern everywhere.
    $toasts = collect([
        'success' => ['message' => session('success'), 'type' => 'success'],
        'error' => ['message' => session('error'), 'type' => 'error'],
        'info' => ['message' => session('info'), 'type' => 'info'],
    ])->filter(fn ($t) => filled($t['message']))->values();
@endphp

<div
    x-data="{ toasts: @js($toasts) }"
    x-init="toasts.forEach((t, i) => setTimeout(() => { t.visible = true }, 50 * i))"
    class="fixed top-4 right-4 z-[9999] space-y-2 w-80"
>
    <template x-for="(toast, index) in toasts" :key="index">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-init="setTimeout(() => toast.visible = false, 4000)"
            class="rounded-lg shadow-lg p-4 flex items-start gap-3"
            :class="{
                'bg-green-50 border border-green-200 text-green-800': toast.type === 'success',
                'bg-red-50 border border-red-200 text-red-800': toast.type === 'error',
                'bg-blue-50 border border-blue-200 text-blue-800': toast.type === 'info',
            }"
        >
            <span x-show="toast.type === 'success'">✓</span>
            <span x-show="toast.type === 'error'">✕</span>
            <span x-show="toast.type === 'info'">ℹ</span>
            <p class="text-sm flex-1" x-text="toast.message"></p>
            <button @click="toast.visible = false" class="text-sm opacity-50 hover:opacity-100">✕</button>
        </div>
    </template>
</div>
