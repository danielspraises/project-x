<?php

use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $newPermissions = [
            ['name' => 'View institution structure (read-only)', 'slug' => 'institution.view', 'group' => 'institution'],
            ['name' => 'View users', 'slug' => 'users.view', 'group' => 'users'],
            ['name' => 'Manage institution roles', 'slug' => 'roles.manage', 'group' => 'users'],
            ['name' => 'Add notes', 'slug' => 'notes.create', 'group' => 'notes'],
            ['name' => 'View notes', 'slug' => 'notes.view', 'group' => 'notes'],
        ];

        foreach ($newPermissions as $permission) {
            Permission::firstOrCreate(['slug' => $permission['slug']], $permission);
        }

        $newPermissionIds = Permission::whereIn('slug', array_column($newPermissions, 'slug'))->pluck('id', 'slug');

        // Backfill: every existing ICT Admin role gets the new permissions too,
        // since they were created before this migration and won't have them otherwise.
        Role::where('slug', 'ict_admin')->whereNotNull('institution_id')->get()->each(function (Role $role) use ($newPermissionIds) {
            $role->permissions()->syncWithoutDetaching($newPermissionIds->values());
        });

        // Create an Auditor role for every existing institution that doesn't have one yet.
        Institution::all()->each(function (Institution $institution) use ($newPermissionIds) {
            if (Role::where('institution_id', $institution->id)->where('slug', 'auditor')->exists()) {
                return;
            }

            $auditorRole = Role::create([
                'institution_id' => $institution->id,
                'name' => 'Auditor',
                'slug' => 'auditor',
            ]);

            $auditPermissionSlugs = ['institution.view', 'audit.view', 'reports.generate', 'notes.create', 'notes.view'];
            $auditorRole->permissions()->attach(
                Permission::whereIn('slug', $auditPermissionSlugs)->pluck('id')
            );
        });
    }

    public function down(): void
    {
        Permission::whereIn('slug', ['institution.view', 'users.view', 'roles.manage', 'notes.create', 'notes.view'])->delete();
        Role::where('slug', 'auditor')->delete();
    }
};
