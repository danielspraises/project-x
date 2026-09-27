<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['name' => 'Manage subjects and subject offerings', 'slug' => 'subjects.manage', 'group' => 'subjects'],
            ['name' => 'Assign student subject registrations', 'slug' => 'subject_registrations.assign', 'group' => 'subjects'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['slug' => $permission['slug']], $permission);
        }

        $permissionIds = Permission::whereIn('slug', [
            'subjects.manage',
            'subject_registrations.assign',
        ])->pluck('id');

        // Existing institutions may have been created before these permissions were
        // added to the ICT Admin defaults. Backfill every ICT Admin role, regardless
        // of institution education level.
        Role::where('slug', 'ict_admin')
            ->get()
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching($permissionIds));
    }

    public function down(): void
    {
        $permissionIds = Permission::whereIn('slug', [
            'subjects.manage',
            'subject_registrations.assign',
        ])->pluck('id');

        Role::where('slug', 'ict_admin')->get()->each(function (Role $role) use ($permissionIds) {
            $role->permissions()->detach($permissionIds);
        });
    }
};
