<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Departments</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-end mb-4">
                <a href="{{ route('ict-admin.departments.create') }}" class="btn-brand-primary px-4 py-2 rounded-md text-sm font-medium">
                    + Add Department
                </a>
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Faculty</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($departments as $department)
                            @php($confirmMsg = 'This will remove "' . $department->name . '" permanently. This cannot be undone.')
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $department->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $department->code }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $department->faculty->name }}</td>
                                <td class="px-6 py-4 text-sm text-right space-x-3">
                                    <a href="{{ route('ict-admin.departments.edit', $department) }}" class="text-brand hover:opacity-75">Edit</a>
                                    <x-delete-button
                                        :action="route('ict-admin.departments.destroy', $department)"
                                        confirm-title="Delete this department?"
                                        :confirm-message="$confirmMsg"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No departments yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
