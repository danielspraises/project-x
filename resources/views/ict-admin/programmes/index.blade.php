<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Programmes</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-end mb-4">
                <a href="{{ route('ict-admin.programmes.create') }}" class="btn-brand-primary px-4 py-2 rounded-md text-sm font-medium">
                    + Add Programme
                </a>
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($programmes as $programme)
                            @php($confirmMsg = 'This will remove "' . $programme->name . '" permanently. This cannot be undone.')
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $programme->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $programme->code }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $programme->department->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $programme->duration_years }} yrs</td>
                                <td class="px-6 py-4 text-sm text-right space-x-3">
                                    <a href="{{ route('ict-admin.programmes.edit', $programme) }}" class="text-brand hover:opacity-75">Edit</a>
                                    <x-delete-button
                                        :action="route('ict-admin.programmes.destroy', $programme)"
                                        confirm-title="Delete this programme?"
                                        :confirm-message="$confirmMsg"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No programmes yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
