<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Class</h2>
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

                <form method="POST" action="{{ route('ict-admin.classes.store') }}" class="space-y-4">
                    @csrf
                    @php
                        $isPrimary = $institution->education_level === 'primary';
                        $namePlaceholder = $isPrimary ? 'e.g. Creche, Nursery 1, Primary 4' : 'e.g. JSS 1, SSS 2';
                        $codePlaceholder = $isPrimary ? 'e.g. CRE, NUR1, PRI4' : 'e.g. JSS1, SSS2';
                    @endphp
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Class name</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="{{ $namePlaceholder }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Code</label>
                        <input type="text" name="code" value="{{ old('code') }}" placeholder="{{ $codePlaceholder }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Order</label>
                        <input type="number" name="order" value="{{ old('order') }}" min="1" max="20"
                               placeholder="{{ $isPrimary ? 'e.g. 1 for Creche, 2 for Nursery 1...' : 'e.g. 1 for JSS 1, 2 for JSS 2...' }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                        <p class="text-xs text-gray-500 mt-1">Controls the display order of classes (lowest first).</p>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('ict-admin.classes.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                        <x-btn-primary type="submit" loading-text="Saving...">Save Class</x-btn-primary>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
