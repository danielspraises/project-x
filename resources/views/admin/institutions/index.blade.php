<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Institutions</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            <div class="flex justify-end mb-4">
                <a href="{{ route('admin.institutions.create') }}" class="btn-brand-primary px-4 py-2 rounded-md text-sm font-medium">
                    + Add Institution
                </a>
            </div>

            {{-- The generated password still needs to stay visible (not auto-dismiss like a toast) since it's shown once and must be copied. --}}
            @if (session('generated_password'))
                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md text-sm">
                    <strong>ICT Admin's temporary password:</strong>
                    <code class="bg-white px-2 py-1 rounded border border-blue-200">{{ session('generated_password') }}</code>
                    <p class="mt-1 text-blue-700">Copy this now and share it securely — it won't be shown again.</p>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ownership</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Billing</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($institutions as $institution)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $institution->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ ucfirst($institution->education_level) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $institution->categoryLabel() }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $institution->ownership ? ucfirst($institution->ownership) : '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    @if ($institution->billingConfig)
                                        {{ ucfirst(str_replace('_', ' ', $institution->billingConfig->billing_type)) }}
                                        ({{ $institution->billingConfig->currency }})
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        @class([
                                            'bg-yellow-100 text-yellow-800' => $institution->status === 'trial',
                                            'bg-green-100 text-green-800' => $institution->status === 'active',
                                            'bg-red-100 text-red-800' => in_array($institution->status, ['suspended', 'cancelled']),
                                        ])">
                                        {{ ucfirst($institution->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <a href="{{ route('admin.institutions.edit', $institution) }}" class="text-brand hover:opacity-75">Manage</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                    No institutions yet. Click "Add Institution" to onboard your first school.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
