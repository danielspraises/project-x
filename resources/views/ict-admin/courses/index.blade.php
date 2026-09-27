<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Courses</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (auth()->user()->hasPermission('courses.manage'))
                <div class="flex justify-end mb-4">
                    <a href="{{ route('ict-admin.courses.create') }}" class="btn-brand-primary px-4 py-2 rounded-md text-sm font-medium">
                        + Add Course
                    </a>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Units</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Offerings</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($courses as $course)
                            @php($confirmMsg = 'This will remove "' . $course->code . ' - ' . $course->title . '" permanently.')
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $course->code }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $course->title }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $course->department->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $course->credit_unit }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $course->offerings_count }}</td>
                                <td class="px-6 py-4 text-sm text-right space-x-3">
                                    @if (auth()->user()->hasPermission('courses.manage'))
                                        <a href="{{ route('ict-admin.courses.edit', $course) }}" class="text-brand hover:opacity-75">Edit</a>
                                        <x-delete-button
                                            :action="route('ict-admin.courses.destroy', $course)"
                                            confirm-title="Delete this course?"
                                            :confirm-message="$confirmMsg"
                                        />
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No courses yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
