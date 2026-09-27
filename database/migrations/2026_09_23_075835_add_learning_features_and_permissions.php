<?php

use App\Models\Feature;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Institution-level feature gates.
         *
         * These are deliberately disabled for existing institutions.
         * Super Admin must explicitly enable them.
         */
        $features = [
            [
                'key' => 'lessons',
                'name' => 'Lessons',
                'description' => 'Create and manage structured lessons for subjects and courses.',
                'category' => 'Learning',
                'is_core' => false,
            ],
            [
                'key' => 'lectures',
                'name' => 'Lectures',
                'description' => 'Create and manage lecture content for subjects and courses.',
                'category' => 'Learning',
                'is_core' => false,
            ],
        ];

        foreach ($features as $featureData) {
            $feature = Feature::firstOrCreate(
                ['key' => $featureData['key']],
                $featureData
            );

            Institution::query()->each(function ($institution) use ($feature) {
                $institution->features()->syncWithoutDetaching([
                    $feature->id => [
                        'enabled' => false,
                        'changed_at' => now(),
                    ],
                ]);
            });
        }

        /*
         * Role-level permissions.
         */
        $permissions = [
            [
                'name' => 'Create lessons',
                'slug' => 'lessons.create',
                'group' => 'lessons',
            ],
            [
                'name' => 'Manage lessons',
                'slug' => 'lessons.manage',
                'group' => 'lessons',
            ],
            [
                'name' => 'Publish lessons',
                'slug' => 'lessons.publish',
                'group' => 'lessons',
            ],
            [
                'name' => 'Create lectures',
                'slug' => 'lectures.create',
                'group' => 'lectures',
            ],
            [
                'name' => 'Manage lectures',
                'slug' => 'lectures.manage',
                'group' => 'lectures',
            ],
            [
                'name' => 'Publish lectures',
                'slug' => 'lectures.publish',
                'group' => 'lectures',
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['slug' => $permissionData['slug']],
                $permissionData
            );
        }

        /*
         * Teachers handle lessons and lectures in primary/secondary.
         */
        $teacherPermissionSlugs = collect($permissions)
            ->pluck('slug')
            ->values();

        $teacherPermissionIds = Permission::whereIn(
            'slug',
            $teacherPermissionSlugs
        )->pluck('id');

        Role::where('slug', 'subject_teacher')
            ->get()
            ->each(function ($role) use ($teacherPermissionIds) {
                $role->permissions()->syncWithoutDetaching($teacherPermissionIds);
            });

        /*
         * Lecturers handle lessons and lectures in tertiary institutions.
         */
        Role::where('slug', 'lecturer')
            ->get()
            ->each(function ($role) use ($teacherPermissionIds) {
                $role->permissions()->syncWithoutDetaching($teacherPermissionIds);
            });
    }

    public function down(): void
    {
        $permissionSlugs = [
            'lessons.create',
            'lessons.manage',
            'lessons.publish',
            'lectures.create',
            'lectures.manage',
            'lectures.publish',
        ];

        $permissionIds = Permission::whereIn(
            'slug',
            $permissionSlugs
        )->pluck('id');

        \DB::table('role_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        Permission::whereIn('id', $permissionIds)->delete();

        $featureIds = Feature::whereIn('key', [
            'lessons',
            'lectures',
        ])->pluck('id');

        \DB::table('institution_features')
            ->whereIn('feature_id', $featureIds)
            ->delete();

        Feature::whereIn('id', $featureIds)->delete();
    }
};