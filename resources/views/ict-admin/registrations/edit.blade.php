<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Register Students — {{ $courseOffering->course->code }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-600 mb-4">
                    {{ $courseOffering->course->title }} — {{ $courseOffering->programme->name }}, {{ $courseOffering->level }}L,
                    {{ $courseOffering->term->name }} ({{ $courseOffering->term->academicSession->name }})
                </p>

                @if ($eligibleStudents->isEmpty())
                    <p class="text-sm text-gray-500">No active students match this offering's programme and level yet.</p>
                @else
                    <form method="POST" action="{{ route('ict-admin.course-offerings.registrations.update', $courseOffering) }}">
                        @csrf @method('PUT')

                        <div class="flex justify-between items-center mb-3">
                            <p class="text-xs text-gray-500">{{ $eligibleStudents->count() }} eligible student(s)</p>
                            <div class="space-x-3 text-xs">
                                <button type="button" onclick="document.querySelectorAll('.student-checkbox').forEach(c => c.checked = true)" class="text-brand hover:opacity-75">Select all</button>
                                <button type="button" onclick="document.querySelectorAll('.student-checkbox').forEach(c => c.checked = false)" class="text-brand hover:opacity-75">Deselect all</button>
                            </div>
                        </div>

                        <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-md divide-y divide-gray-100">
                            @foreach ($eligibleStudents as $student)
                                <label class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                                           class="student-checkbox rounded border-gray-300"
                                           @checked(in_array($student->id, $registeredStudentIds))>
                                    <span class="text-gray-900">{{ $student->fullName() }}</span>
                                    <span class="text-gray-400">({{ $student->admission_number }})</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <a href="{{ route('ict-admin.course-offerings.index') }}" class="px-4 py-2 text-sm text-gray-600">Back</a>
                            <x-btn-primary type="submit" loading-text="Saving...">Save Registrations</x-btn-primary>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
