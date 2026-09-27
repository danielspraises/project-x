<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit {{ $institution->periodLabel() }}</h2>
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

                <form method="POST" action="{{ route('ict-admin.terms.update', $term) }}" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Session</label>
                        <select name="academic_session_id" class="w-full rounded-md border-gray-300 shadow-sm" required>
                            @foreach ($sessions as $session)
                                <option value="{{ $session->id }}" @selected(old('academic_session_id', $term->academic_session_id) == $session->id)>{{ $session->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Name</label>
                        <input type="text" name="name" value="{{ old('name', $term->name) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Order</label>
                        <input type="number" name="order" value="{{ old('order', $term->order) }}" min="1" max="5"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Start date</label>
                        <input type="date" name="start_date" value="{{ old('start_date', $term->start_date->format('Y-m-d')) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">End date</label>
                        <input type="date" name="end_date" value="{{ old('end_date', $term->end_date->format('Y-m-d')) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Status</label>
                        <select name="status" class="w-full rounded-md border-gray-300 shadow-sm" required>
                            @foreach (['upcoming', 'active', 'closed'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $term->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">A closed term blocks new result submissions. Unless explicitly reopened.</p>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('ict-admin.terms.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                        <x-btn-primary type="submit" loading-text="Updating...">Update</x-btn-primary>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
