<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Course Offering</h2>
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

                <form method="POST" action="{{ route('ict-admin.course-offerings.update', $courseOffering) }}" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Course</label>
                        <select name="course_id" class="w-full rounded-md border-gray-300 shadow-sm" required>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" @selected(old('course_id', $courseOffering->course_id) == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">{{ auth()->user()->institution->periodLabel() }}</label>
                        <select name="term_id" class="w-full rounded-md border-gray-300 shadow-sm" required>
                            @foreach ($terms as $term)
                                <option value="{{ $term->id }}" @selected(old('term_id', $courseOffering->term_id) == $term->id)>{{ $term->name }} ({{ $term->academicSession->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Programme</label>
                        <select name="programme_id" class="w-full rounded-md border-gray-300 shadow-sm" required>
                            @foreach ($programmes as $programme)
                                <option value="{{ $programme->id }}" @selected(old('programme_id', $courseOffering->programme_id) == $programme->id)>{{ $programme->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Level</label>
                        <input type="text" name="level" value="{{ old('level', $courseOffering->level) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Lecturer</label>
                        <x-searchable-select
                            name="lecturer_id"
                            :options="$lecturers->map(fn ($l) => ['value' => $l->id, 'label' => $l->name])->values()"
                            :selected="old('lecturer_id', $courseOffering->lecturer_id)"
                            placeholder="Unassigned"
                        />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Requirement type</label>
                        <select name="requirement_type" class="w-full rounded-md border-gray-300 shadow-sm" required>
                            <option value="compulsory" @selected(old('requirement_type', $courseOffering->requirement_type) === 'compulsory')>Compulsory</option>
                            <option value="elective" @selected(old('requirement_type', $courseOffering->requirement_type) === 'elective')>Elective</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('ict-admin.course-offerings.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                        <x-btn-primary type="submit" loading-text="Updating...">Update Offering</x-btn-primary>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
