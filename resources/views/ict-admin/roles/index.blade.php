<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Roles</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-end mb-4">
                <a href="{{ route('ict-admin.roles.create') }}" class="btn-brand-primary px-4 py-2 rounded-md text-sm font-medium">
                    + Add Role
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permissions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Users</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($roles as $role)
                            @php($confirmMsg = 'This will remove the "' . $role->name . '" role permanently.')
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $role->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $role->permissions->count() }} permissions</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $role->users_count }}</td>
                                <td class="px-6 py-4 text-sm text-right space-x-3">
                                    @if ($role->slug === 'ict_admin')
                                        <span class="text-xs text-gray-400 italic">Managed by Administrator</span>
                                    @else
                                        <a href="{{ route('ict-admin.roles.edit', $role) }}" class="text-brand hover:opacity-75">Edit</a>
                                        @if ($role->users_count === 0)
                                            <x-delete-button
                                                :action="route('ict-admin.roles.destroy', $role)"
                                                confirm-title="Delete this role?"
                                                :confirm-message="$confirmMsg"
                                            />
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No roles yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
