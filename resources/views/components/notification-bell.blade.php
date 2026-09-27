@php
    $unreadCount = auth()->user()->unreadNotifications()->count();
    $recentNotifications = auth()->user()->notifications()->latest()->limit(8)->get();
@endphp

<div
    x-data="{ open: false }"
    class="relative"
    @click.outside="open = false"
>
    <button
        type="button"
        @click="open = !open"
        class="relative text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white p-1.5 rounded hover:bg-gray-100 dark:hover:bg-white/10 transition"
        aria-label="Notifications"
    >
        <svg
            class="w-5 h-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
            />
        </svg>

        @if ($unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[16px] h-4 flex items-center justify-center px-1">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="absolute right-0 mt-2 w-80 rounded-md shadow-lg border z-50"
        style="
            display: none;
            background: var(--cx-surface, #ffffff);
            border-color: var(--cx-border, #e5e7eb);
        "
    >
        <div
            class="flex justify-between items-center px-4 py-3 border-b"
            style="border-color: var(--cx-border, #e5e7eb);"
        >
            <p
                class="text-sm font-semibold"
                style="color: var(--cx-text, #374151);"
            >
                Notifications
            </p>

            @if ($unreadCount > 0)
                <form
                    method="POST"
                    action="{{ route('notifications.mark-all-read') }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="text-xs text-brand hover:opacity-75"
                    >
                        Mark all read
                    </button>
                </form>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto content-scroll">
            @forelse ($recentNotifications as $notification)
                <a
                    href="{{ route('notifications.open', $notification->id) }}"
                    class="block px-4 py-3 text-sm border-b hover:bg-gray-50 dark:hover:bg-white/5 transition {{ $notification->read_at ? '' : 'bg-blue-50/50 dark:bg-blue-500/10' }}"
                    style="border-color: var(--cx-border, #f3f4f6);"
                >
                    <div class="flex items-start gap-2">
                        @if (! $notification->read_at)
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5 shrink-0"></span>
                        @endif

                        <div class="min-w-0">
                            <p
                                class="font-medium"
                                style="color: var(--cx-text, #1f2937);"
                            >
                                {{ $notification->data['title'] ?? 'Notification' }}
                            </p>

                            <p
                                class="text-xs mt-0.5"
                                style="color: var(--cx-muted, #6b7280);"
                            >
                                {{ $notification->data['message'] ?? $notification->data['body'] ?? '' }}
                            </p>

                            <p
                                class="text-xs mt-1"
                                style="color: var(--cx-muted, #9ca3af);"
                            >
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </a>
            @empty
                <p
                    class="px-4 py-6 text-sm text-center"
                    style="color: var(--cx-muted, #9ca3af);"
                >
                    No notifications yet.
                </p>
            @endforelse
        </div>
    </div>
</div>