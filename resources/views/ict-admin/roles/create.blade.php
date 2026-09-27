<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Role</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
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

                <form method="POST" action="{{ route('ict-admin.roles.store') }}" class="space-y-6">
                    @csrf
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Role name</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Exams Officer"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 border-b pb-2">Permissions</h3>
                        <div class="space-y-4">
                            @foreach ($permissions as $group => $groupPermissions)
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase mb-2">{{ $group }}</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach ($groupPermissions as $permission)
                                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                                       @checked(collect(old('permissions', []))->contains($permission->id))
                                                       class="rounded border-gray-300">
                                                {{ $permission->name }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('ict-admin.roles.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                        <x-btn-primary type="submit" loading-text="Saving...">Save Role</x-btn-primary>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
