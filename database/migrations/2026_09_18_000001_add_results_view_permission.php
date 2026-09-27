<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['slug' => 'results.view'],
            [
                'name' => 'View Result Engine',
                'group' => 'results',
            ]
        );

        Role::query()
            ->where('slug', 'ict_admin')
            ->whereNotNull('institution_id')
            ->get()
            ->each(function (Role $role) use ($permission) {
                $role->permissions()->syncWithoutDetaching([$permission->id]);
            });
    }

    public function down(): void
    {
        $permission = Permission::where('slug', 'results.view')->first();

        if (! $permission) {
            return;
        }

        Role::query()
            ->where('slug', 'ict_admin')
            ->whereNotNull('institution_id')
            ->get()
            ->each(fn (Role $role) => $role->permissions()->detach($permission->id));

        $permission->delete();
    }
};
