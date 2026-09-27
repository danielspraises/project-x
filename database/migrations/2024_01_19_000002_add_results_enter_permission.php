<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['slug' => 'results.enter'],
            [
                'name' => 'Enter student results',
                'group' => 'results',
            ]
        );

        Role::whereIn('slug', [
            'ict_admin',
            'class_teacher',
            'subject_teacher',
            'lecturer',
        ])->get()->each(function (Role $role) use ($permission) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        });
    }

    public function down(): void
    {
        $permission = Permission::where('slug', 'results.enter')->first();

        if (! $permission) {
            return;
        }

        Role::whereIn('slug', [
            'ict_admin',
            'class_teacher',
            'subject_teacher',
            'lecturer',
        ])->get()->each(function (Role $role) use ($permission) {
            $role->permissions()->detach($permission->id);
        });

        $permission->delete();
    }
};
