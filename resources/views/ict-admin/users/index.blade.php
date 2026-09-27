<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $isTertiary ? 'Staff' : 'Teachers' }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if (session('generated_password'))
                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md text-sm">
                    <strong>Temporary password:</strong>
                    <code class="bg-white px-2 py-1 rounded border border-blue-200">{{ session('generated_password') }}</code>
                    <p class="mt-1 text-blue-700">Copy this now and share it securely. It won't be shown again.</p>
                </div>
            @endif

            <div class="flex justify-end mb-4">
                <div class="flex flex-wrap gap-3">
                    @unless ($isTertiary)
                        <a href="{{ route('ict-admin.class-teachers.index') }}" class="px-4 py-2 rounded-md text-sm font-medium border border-gray-300 text-gray-700 hover:bg-gray-50">
                            Class Teachers
                        </a>
                    @endunless
                    <a href="{{ route('ict-admin.users.create') }}" class="btn-brand-primary px-4 py-2 rounded-md text-sm font-medium">
                        + Add {{ $isTertiary ? 'Staff' : 'Teacher' }}
                    </a>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            @if ($isTertiary)
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            @endif
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($users as $user)
                            @php($isIctAdmin = ($user->role->slug ?? null) === 'ict_admin')
                            @php($confirmMsg = 'This permanently deletes "' . $user->name . '"\'s account. This cannot be undone.')
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $user->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $user->email }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $user->role->name ?? '—' }}</td>
                                @if ($isTertiary)
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $user->department->name ?? '—' }}</td>
                                @endif
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        @class([
                                            'bg-green-100 text-green-800' => $user->status === 'active',
                                            'bg-gray-100 text-gray-800' => $user->status === 'suspended',
                                        ])">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right space-x-3">
                                    @if ($isIctAdmin)
                                        <span class="text-xs text-gray-400 italic">Managed by Administrator</span>
                                    @else
                                        <a href="{{ route('ict-admin.users.edit', $user) }}" class="text-brand hover:opacity-75">Edit</a>
                                        @if (auth()->user()->hasPermission('users.delete') && $user->id !== auth()->id())
                                            <x-delete-button
                                                :action="route('ict-admin.users.destroy', $user)"
                                                confirm-title="Delete this account?"
                                                :confirm-message="$confirmMsg"
                                            />
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isTertiary ? 6 : 5 }}" class="px-6 py-8 text-center text-sm text-gray-500">
                                    No {{ $isTertiary ? 'staff' : 'teachers' }} yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
