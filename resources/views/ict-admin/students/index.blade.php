<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Students</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-end mb-4">
                <a href="{{ route('ict-admin.students.create') }}" class="btn-brand-primary px-4 py-2 rounded-md text-sm font-medium">
                    + Add Student
                </a>
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admission No.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department / Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($students as $student)
                            @php($confirmMsg = 'This will remove "' . $student->fullName() . '" permanently. This cannot be undone.')
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $student->admission_number }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $student->fullName() }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    @if ($student->department)
                                        {{ $student->department->name }} ({{ $student->level }}L)
                                    @elseif ($student->schoolClass)
                                        {{ $student->schoolClass->name }} {{ $student->arm?->name }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        @class([
                                            'bg-green-100 text-green-800' => $student->status === 'active',
                                            'bg-blue-100 text-blue-800' => $student->status === 'graduated',
                                            'bg-gray-100 text-gray-800' => $student->status === 'withdrawn',
                                            'bg-red-100 text-red-800' => $student->status === 'suspended',
                                        ])">
                                        {{ ucfirst($student->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right space-x-3">
                                    <a href="{{ route('ict-admin.students.edit', $student) }}" class="text-brand hover:opacity-75">Edit</a>
                                    <x-delete-button
                                        :action="route('ict-admin.students.destroy', $student)"
                                        confirm-title="Remove this student record?"
                                        :confirm-message="$confirmMsg"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No students yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $students->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
