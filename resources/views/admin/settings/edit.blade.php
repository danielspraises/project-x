<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Platform Settings</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4 border-b pb-2">Your branding</h3>
                <p class="text-xs text-gray-500 mb-4">
                    These colors apply across your Super Admin view — the sidebar, buttons, and links you see when managing the platform.
                    Each institution's own branding is separate and set by their ICT Admin (or by you, from Manage → Institution).
                </p>

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md text-red-800 text-sm">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Primary color</label>
                            <div class="flex items-center gap-2">
                                <input type="color" name="primary_color" value="{{ old('primary_color', $branding['primary_color']) }}"
                                       class="h-10 w-14 rounded border-gray-300">
                                <span class="text-sm text-gray-500">{{ old('primary_color', $branding['primary_color']) }}</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Secondary color</label>
                            <input type="color" name="secondary_color" value="{{ old('secondary_color', $branding['secondary_color'] ?? '#6b7280') }}"
                                   class="h-10 w-14 rounded border-gray-300">
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <x-btn-primary type="submit" loading-text="Saving...">Save Branding</x-btn-primary>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
