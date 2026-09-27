<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Course</h2>
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

                @if ($departments->isEmpty())
                    <p class="text-sm text-gray-600">You need a department first.
                        <a href="{{ route('ict-admin.departments.create') }}" class="text-brand underline">Add one</a>.</p>
                @else
                    <form method="POST" action="{{ route('ict-admin.courses.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Department</label>
                            <select name="department_id" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="">Select department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Course code</label>
                            <input type="text" name="code" value="{{ old('code') }}" placeholder="e.g. CSC201"
                                   class="w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Title</label>
                            <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g. Data Structures and Algorithms"
                                   class="w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Credit unit</label>
                            <input type="number" name="credit_unit" value="{{ old('credit_unit', 3) }}" min="1" max="10"
                                   class="w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Level</label>
                            <input type="text" name="level" value="{{ old('level') }}" placeholder="e.g. 200"
                                   class="w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <a href="{{ route('ict-admin.courses.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                            <x-btn-primary type="submit" loading-text="Saving...">Save Course</x-btn-primary>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
