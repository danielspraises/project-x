<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Subject</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ route('ict-admin.subjects.update', $subject) }}">
                    @csrf
                    @method('PUT')

                    {{-- Name --}}
                    <div class="mb-4">
                        <x-input-label for="name" value="Subject Name" />
                        <x-text-input id="name" name="name" type="text"
                            class="mt-1 block w-full"
                            :value="old('name', $subject->name)"
                            required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    {{-- Code --}}
                    <div class="mb-4">
                        <x-input-label for="code" value="Subject Code (optional)" />
                        <x-text-input id="code" name="code" type="text"
                            class="mt-1 block w-full"
                            :value="old('code', $subject->code)" />
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>

                    {{-- Category --}}
                    <div class="mb-6">
                        <x-input-label for="category" value="Category (optional)" />
                        <x-text-input id="category" name="category" type="text"
                            class="mt-1 block w-full"
                            :value="old('category', $subject->category)"
                            placeholder="e.g. Core, Elective, Vocational" />
                        <x-input-error :messages="$errors->get('category')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Update Subject</x-primary-button>
                        <a href="{{ route('ict-admin.subjects.index') }}"
                           class="text-sm text-gray-600 hover:underline">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>