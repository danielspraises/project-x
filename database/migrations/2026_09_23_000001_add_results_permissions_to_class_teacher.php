<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $approve = Permission::firstOrCreate(
            ['slug' => 'results.approve'],
            [
                'name' => 'Approve class result submissions',
                'group' => 'results',
            ]
        );

        $enter = Permission::firstOrCreate(
            ['slug' => 'results.enter'],
            [
                'name' => 'Enter student results',
                'group' => 'results',
            ]
        );

        Role::where('slug', 'class_teacher')
            ->whereNotNull('institution_id')
            ->get()
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$approve->id, $enter->id]));
    }

    public function down(): void
    {
        $slugs = Permission::whereIn('slug', ['results.approve', 'results.enter'])->pluck('id', 'slug');

        if ($slugs->isEmpty()) {
            return;
        }

        Role::where('slug', 'class_teacher')
            ->whereNotNull('institution_id')
            ->get()
            ->each(fn (Role $role) => $role->permissions()->detach($slugs->values()));

        // Not deleting the permission rows themselves — other roles
        // (ict_admin, hod, lecturer, subject_teacher) already depend on them.
    }
};
