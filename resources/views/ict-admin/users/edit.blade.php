<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit User</h2>
    </x-slot>

    <div class="py-8" x-data="{
            roleId: @js(old('role_id', (string) $user->role_id)),
            roleSlugs: @js($roles->pluck('slug', 'id')),
            departmentScoped: @js($departmentScopedRoleSlugs ?? []),
            get needsDepartment() {
                return this.departmentScoped.includes(this.roleSlugs[this.roleId]);
            }
        }">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
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

                @if (session('generated_password'))
                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md text-sm">
                        <strong>Temporary password:</strong>
                        <code class="bg-white px-2 py-1 rounded border border-blue-200">{{ session('generated_password') }}</code>
                        <p class="mt-1 text-blue-700">Copy this now and share it securely. It won't be shown again.</p>
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-md text-green-800 text-xs">
                        {{ session('success') }}
                    </div>
                @endif

                @php($isSelf = $user->id === auth()->id())

                @if ($isSelf)
                    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md text-blue-800 text-xs">
                        Managed by Administrator.
                    </div>
                @endif

                <form method="POST" action="{{ route('ict-admin.users.update', $user) }}" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Full name</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Role</label>
                        @if ($isSelf)
                            <input type="text" value="{{ $user->role->name ?? '—' }}" disabled
                                   class="w-full rounded-md border-gray-200 bg-gray-50 text-gray-500 shadow-sm">
                        @else
                            <select name="role_id" x-model="roleId" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    @if ($isTertiary && !$isSelf)
                        <div x-show="needsDepartment" x-cloak>
                            <label class="block text-sm text-gray-600 mb-1">Department</label>
                            <x-searchable-select
                                name="department_id"
                                :options="$departments->map(fn ($d) => ['value' => $d->id, 'label' => $d->name])->values()"
                                :selected="old('department_id', $user->department_id)"
                                placeholder="Search departments..."
                            />
                            <p class="text-xs text-gray-500 mt-1">
                                This role operates within one department — results, students, and approvals will be scoped to it.
                            </p>
                        </div>
                    @elseif ($isTertiary && $isSelf && $user->department)
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Department</label>
                            <input type="text" value="{{ $user->department->name }}" disabled
                                   class="w-full rounded-md border-gray-200 bg-gray-50 text-gray-500 shadow-sm">
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Status</label>
                        @if ($isSelf)
                            <input type="text" value="{{ ucfirst($user->status) }}" disabled
                                   class="w-full rounded-md border-gray-200 bg-gray-50 text-gray-500 shadow-sm">
                        @else
                            <select name="status" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="active" @selected(old('status', $user->status) === 'active')>Active</option>
                                <option value="suspended" @selected(old('status', $user->status) === 'suspended')>Suspended</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Suspending blocks their login without deleting the account.</p>
                        @endif
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('ict-admin.users.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                        <x-btn-primary type="submit" loading-text="Updating...">Update User</x-btn-primary>
                    </div>
                </form>
            </div>

            @if ($courseAssignment)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-1">Course Assignments</h3>
                    <p class="text-xs text-gray-500 mb-4">Choose which course offerings this lecturer teaches, per term.</p>

                    <form method="GET" action="{{ route('ict-admin.users.edit', $user) }}" class="mb-4">
                        <label class="block text-sm text-gray-600 mb-1">Term</label>
                        <select name="term_id" class="w-full rounded-md border-gray-300 shadow-sm" onchange="this.form.submit()">
                            @foreach ($courseAssignment['terms'] as $term)
                                <option value="{{ $term->id }}" @selected($courseAssignment['selectedTermId'] == $term->id)>
                                    {{ $term->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>

                    <form method="POST" action="{{ route('ict-admin.users.course-offerings.update', $user) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="term_id" value="{{ $courseAssignment['selectedTermId'] }}">

                        <label class="block text-sm text-gray-600 mb-1">Course offerings</label>
                        <x-searchable-select
                            name="course_offering_ids"
                            :options="$courseAssignment['options']"
                            :selected="$courseAssignment['assignedIds']"
                            placeholder="Search courses..."
                            :multiple="true"
                        />
                        @if ($courseAssignment['options']->isEmpty())
                            <p class="text-xs text-amber-600 mt-1">No course offerings exist for this term yet.</p>
                        @endif

                        <div class="flex justify-end pt-4">
                            <x-btn-primary type="submit" loading-text="Saving...">Save Assignments</x-btn-primary>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
