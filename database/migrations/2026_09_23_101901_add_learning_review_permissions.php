<?php

use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $lessonReview = Permission::firstOrCreate(
            ['slug' => 'lessons.review'],
            [
                'name' => 'Review Lessons',
                'group' => 'Lessons',
            ]
        );

        $lectureReview = Permission::firstOrCreate(
            ['slug' => 'lectures.review'],
            [
                'name' => 'Review Lectures',
                'group' => 'Lectures',
            ]
        );

        /*
         * Primary/Secondary:
         * Create the appropriate academic reviewer roles
         * inside each institution.
         */
        Institution::query()
            ->whereIn('education_level', ['primary', 'secondary'])
            ->each(function (Institution $institution) use ($lessonReview) {
                $roles = $institution->education_level === 'primary'
                    ? [
                        [
                            'name' => 'HM',
                            'slug' => 'hm',
                        ],
                        [
                            'name' => 'Vice HM (Academics)',
                            'slug' => 'vice_hm_academics',
                        ],
                    ]
                    : [
                        [
                            'name' => 'Principal',
                            'slug' => 'principal',
                        ],
                        [
                            'name' => 'Vice Principal',
                            'slug' => 'vice_principal',
                        ],
                        [
                            'name' => 'Vice Principal (Academics)',
                            'slug' => 'vice_principal_academics',
                        ],
                    ];

                foreach ($roles as $roleData) {
                    $role = Role::firstOrCreate(
                        [
                            'institution_id' => $institution->id,
                            'slug' => $roleData['slug'],
                        ],
                        [
                            'name' => $roleData['name'],
                        ]
                    );

                    $role->permissions()->syncWithoutDetaching([
                        $lessonReview->id,
                    ]);
                }
            });

        /*
         * Tertiary:
         * Existing HOD roles receive lecture review permission.
         */
        Institution::query()
            ->where('education_level', 'tertiary')
            ->each(function (Institution $institution) use ($lectureReview) {
                Role::query()
                    ->where('institution_id', $institution->id)
                    ->where('slug', 'hod')
                    ->each(function (Role $role) use ($lectureReview) {
                        $role->permissions()->syncWithoutDetaching([
                            $lectureReview->id,
                        ]);
                    });
            });
    }

    public function down(): void
    {
        $lessonReview = Permission::where('slug', 'lessons.review')->first();
        $lectureReview = Permission::where('slug', 'lectures.review')->first();

        if ($lessonReview) {
            Role::query()
                ->whereIn('slug', [
                    'hm',
                    'vice_hm_academics',
                    'principal',
                    'vice_principal',
                    'vice_principal_academics',
                ])
                ->each(function (Role $role) use ($lessonReview) {
                    $role->permissions()->detach($lessonReview->id);
                });
        }

        if ($lectureReview) {
            Role::query()
                ->where('slug', 'hod')
                ->each(function (Role $role) use ($lectureReview) {
                    $role->permissions()->detach($lectureReview->id);
                });
        }

        /*
         * Remove only roles created by this migration.
         * Existing roles are left untouched.
         */
        Role::query()
            ->whereIn('slug', [
                'hm',
                'vice_hm_academics',
                'principal',
                'vice_principal',
                'vice_principal_academics',
            ])
            ->delete();

        $lessonReview?->delete();
        $lectureReview?->delete();
    }
};