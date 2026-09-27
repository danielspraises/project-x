<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Academic Session</h2>
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

                <form method="POST" action="{{ route('ict-admin.sessions.update', $session) }}" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Session name</label>
                        <input type="text" name="name" value="{{ old('name', $session->name) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Start date</label>
                        <input type="date" name="start_date" value="{{ old('start_date', $session->start_date->format('Y-m-d')) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">End date</label>
                        <input type="date" name="end_date" value="{{ old('end_date', $session->end_date->format('Y-m-d')) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Status</label>
                        <select name="status" class="w-full rounded-md border-gray-300 shadow-sm" required>
                            @foreach (['upcoming', 'active', 'closed'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $session->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('ict-admin.sessions.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                        <x-btn-primary type="submit" loading-text="Updating...">Update Session</x-btn-primary>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
