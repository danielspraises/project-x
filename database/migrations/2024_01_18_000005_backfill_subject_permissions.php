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

        $subjectManage = Permission::where('slug', 'subjects.manage')->value('id');
        $registrationAssign = Permission::where('slug', 'subject_registrations.assign')->value('id');

        Role::where('slug', 'ict_admin')->get()->each(fn ($role) => $role->permissions()->syncWithoutDetaching([$subjectManage, $registrationAssign]));
        Role::where('slug', 'class_teacher')->get()->each(fn ($role) => $role->permissions()->syncWithoutDetaching([$registrationAssign]));
    }

    public function down(): void
    {
        $permissionIds = Permission::whereIn('slug', ['subjects.manage', 'subject_registrations.assign'])->pluck('id');
        \DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        Permission::whereIn('id', $permissionIds)->delete();
    }
};
