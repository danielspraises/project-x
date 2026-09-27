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

        // Existing basic-education class teachers were created with approval
        // permission only. Result entry also needs to be explicit.
        Role::where('slug', 'class_teacher')
            ->whereNotNull('institution_id')
            ->get()
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$permission->id]));
    }

    public function down(): void
    {
        // Keep the permission itself because other roles may legitimately use it.
    }
};
