<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $slugs = ['students.manage', 'notes.create', 'notes.view'];
        $permissionIds = Permission::whereIn('slug', $slugs)->pluck('id');

        Role::where('slug', 'hod')->whereNotNull('institution_id')->get()->each(function (Role $role) use ($permissionIds) {
            $role->permissions()->syncWithoutDetaching($permissionIds);
        });
    }

    public function down(): void
    {
        // No-op — removing permissions retroactively isn't safe to automate.
    }
};
