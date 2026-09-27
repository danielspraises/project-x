<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['slug' => 'users.delete'],
            ['name' => 'Delete user accounts', 'slug' => 'users.delete', 'group' => 'users']
        );

        // Give every existing institution's ICT Admin role this permission —
        // new institutions get it automatically via the DEFAULT_ROLES list going forward.
        Role::where('slug', 'ict_admin')->whereNotNull('institution_id')->get()->each(function (Role $role) use ($permission) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        });
    }

    public function down(): void
    {
        Permission::where('slug', 'users.delete')->delete();
    }
};
