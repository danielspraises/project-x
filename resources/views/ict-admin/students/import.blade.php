<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bulk Import Students</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('import_errors') && count(session('import_errors')) > 0)
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-md text-amber-900 text-sm">
                    <p class="font-medium mb-2">{{ count(session('import_errors')) }} row(s) had issues and were skipped:</p>
                    <ul class="list-disc list-inside space-y-1 max-h-48 overflow-y-auto">
                        @foreach (session('import_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Step 1 — Download the template</h3>
                <p class="text-sm text-gray-600 mb-4">
                    This gives you the exact column headers this institution needs
                    ({{ $institution->education_level === 'tertiary' ? 'department, programme, and level codes' : 'class and arm names' }}).
                    Fill it out in Excel or Google Sheets, then save/export as CSV before uploading.
                </p>
                <a href="{{ route('ict-admin.students.import.template') }}"
                   class="inline-block px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 transition">
                    Download CSV Template
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Step 2 — Upload your completed CSV</h3>

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md text-red-800 text-sm">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('ict-admin.students.import.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <input type="file" name="file" accept=".csv,.txt" class="w-full text-sm" required>
                        <p class="text-xs text-gray-500 mt-1">CSV file, up to 5MB. Codes (department, programme, class) must match exactly what's set up in your institution.</p>
                    </div>
                    <div class="flex justify-end">
                        <x-btn-primary type="submit" loading-text="Importing...">Import Students</x-btn-primary>
                    </div>
                </form>
            </div>

            <a href="{{ route('ict-admin.students.index') }}" class="text-sm text-gray-600 hover:text-gray-900 inline-block">&larr; Back to Students</a>
        </div>
    </div>
</x-app-layout>
