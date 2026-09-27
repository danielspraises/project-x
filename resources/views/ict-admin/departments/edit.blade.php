<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Department</h2>
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

                <form method="POST" action="{{ route('ict-admin.departments.update', $department) }}" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Faculty</label>
                        <select name="faculty_id" class="w-full rounded-md border-gray-300 shadow-sm" required>
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty->id }}" @selected(old('faculty_id', $department->faculty_id) == $faculty->id)>{{ $faculty->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Department name</label>
                        <input type="text" name="name" value="{{ old('name', $department->name) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Code</label>
                        <input type="text" name="code" value="{{ old('code', $department->code) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('ict-admin.departments.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                        <x-btn-primary type="submit" loading-text="Updating...">Update Department</x-btn-primary>
                    </div>
                </form>
            </div>

            <div class="mt-6">
                <x-notes-panel :notable="$department" :store-route="route('ict-admin.notes.store', ['type' => 'department', 'id' => $department->id])" />
            </div>
        </div>
    </div>
</x-app-layout>
