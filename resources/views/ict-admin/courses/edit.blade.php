<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Course</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md text-red-800 text-sm">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('ict-admin.courses.update', $course) }}" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Department</label>
                        <select name="department_id" class="w-full rounded-md border-gray-300 shadow-sm" required>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected(old('department_id', $course->department_id) == $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Course code</label>
                        <input type="text" name="code" value="{{ old('code', $course->code) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Title</label>
                        <input type="text" name="title" value="{{ old('title', $course->title) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Credit unit</label>
                        <input type="number" name="credit_unit" value="{{ old('credit_unit', $course->credit_unit) }}" min="1" max="10"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Level</label>
                        <input type="text" name="level" value="{{ old('level', $course->level) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('ict-admin.courses.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                        <x-btn-primary type="submit" loading-text="Updating...">Update Course</x-btn-primary>
                    </div>
                </form>
            </div>

            <div class="mt-6">
                <x-notes-panel :notable="$course" :store-route="route('ict-admin.notes.store', ['type' => 'course', 'id' => $course->id])" />
            </div>
        </div>
    </div>
</x-app-layout>
