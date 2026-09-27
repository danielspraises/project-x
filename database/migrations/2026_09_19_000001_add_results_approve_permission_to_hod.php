<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['slug' => 'results.approve'],
            [
                'name' => 'Approve department result submissions',
                'group' => 'results',
            ]
        );

        Role::where('slug', 'hod')
            ->whereNotNull('institution_id')
            ->get()
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$permission->id]));
    }

    public function down(): void
    {
        $permission = Permission::where('slug', 'results.approve')->first();

        if (! $permission) {
            return;
        }

        Role::where('slug', 'hod')
            ->whereNotNull('institution_id')
            ->get()
            ->each(fn (Role $role) => $role->permissions()->detach($permission->id));

        // Note: not deleting the permission itself — class_teacher already
        // depends on 'results.approve' (see 2024_01_12_000002), so removing
        // the row here would break that role's grant too.
    }
};
