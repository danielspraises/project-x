<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Arm / Section</h2>
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

                @if ($classes->isEmpty())
                    <p class="text-sm text-gray-600">You need a class first.
                        <a href="{{ route('ict-admin.classes.create') }}" class="text-brand underline">Add a class</a>.</p>
                @else
                    <form method="POST" action="{{ route('ict-admin.arms.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Class</label>
                            <select name="class_id" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="">Select class</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Arm name</label>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Gold, A, Diamond"
                                   class="w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <a href="{{ route('ict-admin.arms.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                            <x-btn-primary type="submit" loading-text="Saving...">Save Arm</x-btn-primary>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
