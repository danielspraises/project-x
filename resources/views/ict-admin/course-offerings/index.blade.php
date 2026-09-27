<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Course Offerings</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (auth()->user()->hasPermission('courses.manage'))
                <div class="flex justify-end mb-4">
                    <a href="{{ route('ict-admin.course-offerings.create') }}" class="btn-brand-primary px-4 py-2 rounded-md text-sm font-medium">
                        + Add Offering
                    </a>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase rounded-tl-lg">Course</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Term</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Programme / Level</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lecturer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 rounded-tr-lg"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($offerings as $offering)
                            @php($confirmMsg = 'This will remove the offering for "' . $offering->course->code . '" permanently.')
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $offering->course->code }} — {{ $offering->course->title }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $offering->term->name }} ({{ $offering->term->academicSession->name }})</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $offering->programme->name }} — {{ $offering->level }}L</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $offering->lecturer->name ?? '— Unassigned' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        @class([
                                            'bg-blue-100 text-blue-800' => $offering->requirement_type === 'compulsory',
                                            'bg-gray-100 text-gray-800' => $offering->requirement_type === 'elective',
                                        ])">
                                        {{ ucfirst($offering->requirement_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <x-dropdown align="right" width="48">
                                        <x-slot name="trigger">
                                            <button class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-xs font-medium hover:bg-gray-200 transition inline-flex items-center gap-1">
                                                Actions
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </x-slot>
                                        <x-slot name="content">
                                            @if (auth()->user()->hasPermission('registrations.assign'))
                                                <x-dropdown-link :href="route('ict-admin.course-offerings.registrations.edit', $offering)">
                                                    Register Students
                                                </x-dropdown-link>
                                            @endif
                                            @if (auth()->user()->hasPermission('courses.manage'))
                                                <x-dropdown-link :href="route('ict-admin.course-offerings.edit', $offering)">
                                                    Edit
                                                </x-dropdown-link>
                                                <div class="px-4 py-2 border-t border-gray-100 mt-1 pt-2">
                                                    <x-delete-button
                                                        :action="route('ict-admin.course-offerings.destroy', $offering)"
                                                        confirm-title="Delete this offering?"
                                                        :confirm-message="$confirmMsg"
                                                    />
                                                </div>
                                            @endif
                                        </x-slot>
                                    </x-dropdown>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No course offerings yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
