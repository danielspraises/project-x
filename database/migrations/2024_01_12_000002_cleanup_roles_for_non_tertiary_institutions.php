<?php

use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $tertiaryOnlySlugs = ['faculty_officer', 'department_officer', 'hod', 'lecturer'];

        Institution::where('education_level', '!=', 'tertiary')->get()->each(function (Institution $institution) use ($tertiaryOnlySlugs) {
            // Only remove roles that have zero users — never delete a role someone is actually using.
            Role::where('institution_id', $institution->id)
                ->whereIn('slug', $tertiaryOnlySlugs)
                ->withCount('users')
                ->get()
                ->filter(fn ($role) => $role->users_count === 0)
                ->each(fn ($role) => $role->delete());

            // Add the correct basic-education roles if they don't already exist.
            $basicRoles = [
                'class_teacher' => ['name' => 'Class Teacher', 'permissions' => ['students.manage', 'results.approve', 'reports.generate', 'notes.create', 'notes.view']],
                'subject_teacher' => ['name' => 'Subject Teacher', 'permissions' => ['results.enter']],
            ];

            foreach ($basicRoles as $slug => $definition) {
                if (Role::where('institution_id', $institution->id)->where('slug', $slug)->exists()) {
                    continue;
                }

                $role = Role::create(['institution_id' => $institution->id, 'name' => $definition['name'], 'slug' => $slug]);
                $role->permissions()->attach(Permission::whereIn('slug', $definition['permissions'])->pluck('id'));
            }
        });
    }

    public function down(): void
    {
        // No-op — this is a one-way data cleanup, not safe to automatically reverse.
    }
};
