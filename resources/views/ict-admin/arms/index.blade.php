<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Arms / Sections</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-end mb-4">
                <a href="{{ route('ict-admin.arms.create') }}" class="btn-brand-primary px-4 py-2 rounded-md text-sm font-medium">
                    + Add Arm
                </a>
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Arm name</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($arms as $arm)
                            @php($confirmMsg = 'This will remove "' . $arm->schoolClass->name . ' ' . $arm->name . '" permanently. This cannot be undone.')
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $arm->schoolClass->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $arm->name }}</td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <x-delete-button
                                        :action="route('ict-admin.arms.destroy', $arm)"
                                        confirm-title="Delete this arm?"
                                        :confirm-message="$confirmMsg"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500">
                                    No arms yet. e.g. "JSS 2 Gold", "Primary 4A".
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
