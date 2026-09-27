<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add {{ $isTertiary ? 'Staff' : 'Teacher' }}</h2>
    </x-slot>

    <div class="py-8" x-data="{
            roleId: @js(old('role_id', '')),
            roleSlugs: @js($roles->pluck('slug', 'id')),
            departmentScoped: @js($departmentScopedRoleSlugs ?? []),
            get needsDepartment() {
                return this.departmentScoped.includes(this.roleSlugs[this.roleId]);
            }
        }">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md text-red-800 text-sm">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($roles->isEmpty())
                    <p class="text-sm text-gray-600">You need at least one role first.
                        <a href="{{ route('ict-admin.roles.create') }}" class="text-brand underline">Add a role</a>.</p>
                @else
                    <form method="POST" action="{{ route('ict-admin.users.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Full name</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Role</label>
                            <select name="role_id" x-model="roleId" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="">Select role</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($isTertiary)
                            <div x-show="needsDepartment" x-cloak>
                                <label class="block text-sm text-gray-600 mb-1">Department</label>
                                <x-searchable-select
                                    name="department_id"
                                    :options="$departments->map(fn ($d) => ['value' => $d->id, 'label' => $d->name])->values()"
                                    :selected="old('department_id')"
                                    placeholder="Search departments..."
                                />
                                <p class="text-xs text-gray-500 mt-1">
                                    This role operates within one department — results, students, and approvals will be scoped to it.
                                </p>
                            </div>
                        @endif

                        <p class="text-xs text-gray-500">
                            A temporary password will be generated automatically. You'll see it once, right after saving.
                        </p>

                        <div class="flex justify-end gap-3 pt-2">
                            <a href="{{ route('ict-admin.users.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                            <x-btn-primary type="submit" loading-text="Saving...">Save User</x-btn-primary>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
