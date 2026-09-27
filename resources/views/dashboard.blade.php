<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-gray-600">
                    Welcome back, <span class="font-medium text-gray-900">{{ Auth::user()->name }}</span>.
                    @if (! Auth::user()->isSuperAdmin() && Auth::user()->institution)
                        You're managing <span class="font-medium text-gray-900">{{ Auth::user()->institution->name }}</span>.
                    @endif
                </p>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ($stats as $label => $count)
                    <div class="bg-white shadow-sm sm:rounded-lg p-5">
                        <p class="text-sm text-gray-500">{{ $label }}</p>
                        <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $count }}</p>
                    </div>
                @endforeach
            </div>

            <!-- Quick actions -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Quick actions</h3>
                <div class="flex flex-wrap gap-3">
                    @if (Auth::user()->isSuperAdmin())
                        <a href="{{ route('admin.institutions.create') }}" class="btn-brand-primary px-4 py-2 rounded-md text-sm font-medium">
                            + Add Institution
                        </a>
                        <a href="{{ route('admin.institutions.index') }}"
                           class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200">
                            View All Institutions
                        </a>
                    @elseif (Auth::user()->hasPermission('institution.setup'))
                        @php($educationLevel = Auth::user()->institution->education_level ?? 'tertiary')

                        @if ($educationLevel === 'tertiary')
                            <a href="{{ route('ict-admin.faculties.create') }}" class="btn-brand-primary px-4 py-2 rounded-md text-sm font-medium">
                                + Add Faculty
                            </a>
                            <a href="{{ route('ict-admin.departments.create') }}"
                               class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200">
                                + Add Department
                            </a>
                        @else
                            <a href="{{ route('ict-admin.classes.create') }}" class="btn-brand-primary px-4 py-2 rounded-md text-sm font-medium">
                                + Add Class
                            </a>
                            <a href="{{ route('ict-admin.arms.create') }}"
                               class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200">
                                + Add Arm
                            </a>
                        @endif
                        <a href="{{ route('ict-admin.students.create') }}"
                           class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200">
                            + Add Student
                        </a>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
