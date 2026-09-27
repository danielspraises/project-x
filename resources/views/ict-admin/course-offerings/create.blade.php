<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Course Offering</h2>
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

                @if ($courses->isEmpty() || $terms->isEmpty() || $programmes->isEmpty())
                    <p class="text-sm text-gray-600">
                        You need at least one course, one {{ strtolower(auth()->user()->institution->periodLabel()) }}, and one programme set up before creating an offering.
                    </p>
                @else
                    <form method="POST" action="{{ route('ict-admin.course-offerings.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Course</label>
                            <select name="course_id" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="">Select course</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">{{ auth()->user()->institution->periodLabel() }}</label>
                            <select name="term_id" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="">Select</option>
                                @foreach ($terms as $term)
                                    <option value="{{ $term->id }}" @selected(old('term_id') == $term->id)>{{ $term->name }} ({{ $term->academicSession->name }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Programme</label>
                            <select name="programme_id" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="">Select programme</option>
                                @foreach ($programmes as $programme)
                                    <option value="{{ $programme->id }}" @selected(old('programme_id') == $programme->id)>{{ $programme->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Level</label>
                            <input type="text" name="level" value="{{ old('level') }}" placeholder="e.g. 200"
                                   class="w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Lecturer (optional)</label>
                            <x-searchable-select
                                name="lecturer_id"
                                :options="$lecturers->map(fn ($l) => ['value' => $l->id, 'label' => $l->name])->values()"
                                :selected="old('lecturer_id')"
                                placeholder="Unassigned"
                            />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Requirement type</label>
                            <select name="requirement_type" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="compulsory" @selected(old('requirement_type', 'compulsory') === 'compulsory')>Compulsory</option>
                                <option value="elective" @selected(old('requirement_type') === 'elective')>Elective</option>
                            </select>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <a href="{{ route('ict-admin.course-offerings.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                            <x-btn-primary type="submit" loading-text="Saving...">Save Offering</x-btn-primary>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
