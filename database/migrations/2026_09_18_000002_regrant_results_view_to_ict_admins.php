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

        // Grant the Result Engine permission to every institution-scoped
        // ICT Admin role. This is intentionally broader than the previous
        // migration so existing role variations are not left with a 403.
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
        // Do not remove the permission itself here because it may have been
        // intentionally assigned to roles after this migration runs.
    }
};
