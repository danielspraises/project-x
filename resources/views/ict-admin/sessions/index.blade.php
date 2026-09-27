<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Academic Sessions</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (auth()->user()->hasPermission('institution.setup'))
                <div class="flex justify-end mb-4">
                    <a href="{{ route('ict-admin.sessions.create') }}" class="btn-brand-primary px-4 py-2 rounded-md text-sm font-medium">
                        + Add Session
                    </a>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $institution->periodLabel() }}s</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($sessions as $session)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $session->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $session->start_date->format('M Y') }} – {{ $session->end_date->format('M Y') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $session->terms_count }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        @class([
                                            'bg-yellow-100 text-yellow-800' => $session->status === 'upcoming',
                                            'bg-green-100 text-green-800' => $session->status === 'active',
                                            'bg-gray-100 text-gray-800' => $session->status === 'closed',
                                        ])">
                                        {{ ucfirst($session->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    @if (auth()->user()->hasPermission('institution.setup'))
                                        <a href="{{ route('ict-admin.sessions.edit', $session) }}" class="text-brand hover:opacity-75">Edit</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No sessions yet. e.g. "2025/2026".</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
