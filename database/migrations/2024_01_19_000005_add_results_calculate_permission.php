<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['slug' => 'results.calculate'],
            [
                'name' => 'Calculate student GPA',
                'group' => 'results',
            ]
        );

        Role::where('slug', 'ict_admin')
            ->whereNotNull('institution_id')
            ->get()
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$permission->id]));
    }

    public function down(): void
    {
        $permission = Permission::where('slug', 'results.calculate')->first();

        if (! $permission) {
            return;
        }

        Role::where('slug', 'ict_admin')
            ->whereNotNull('institution_id')
            ->get()
            ->each(fn (Role $role) => $role->permissions()->detach($permission->id));

        $permission->delete();
    }
};
