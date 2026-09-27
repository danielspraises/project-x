<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add {{ $institution->periodLabel() }}</h2>
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

                @if ($sessions->isEmpty())
                    <p class="text-sm text-gray-600">You need an academic session first.
                        <a href="{{ route('ict-admin.sessions.create') }}" class="text-brand underline">Add one</a>.</p>
                @else
                    <form method="POST" action="{{ route('ict-admin.terms.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Session</label>
                            <select name="academic_session_id" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="">Select session</option>
                                @foreach ($sessions as $session)
                                    <option value="{{ $session->id }}" @selected(old('academic_session_id') == $session->id)>{{ $session->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Name</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   placeholder="{{ $institution->education_level === 'tertiary' ? 'e.g. First Semester' : 'e.g. First Term' }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Order</label>
                            <input type="number" name="order" value="{{ old('order') }}" min="1" max="5"
                                   placeholder="1, 2, or 3" class="w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Start date</label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">End date</label>
                            <input type="date" name="end_date" value="{{ old('end_date') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <a href="{{ route('ict-admin.terms.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                            <x-btn-primary type="submit" loading-text="Saving...">Save</x-btn-primary>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
