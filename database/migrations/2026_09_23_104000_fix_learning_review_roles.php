<?php

use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $lessonReview = Permission::where('slug', 'lessons.review')->firstOrFail();
        $lectureReview = Permission::where('slug', 'lectures.review')->firstOrFail();

        /*
         * Primary institutions.
         */
        Institution::where('education_level', 'primary')
            ->each(function (Institution $institution) use ($lessonReview) {
                foreach ([
                    [
                        'name' => 'HM',
                        'slug' => 'hm',
                    ],
                    [
                        'name' => 'Vice HM (Academics)',
                        'slug' => 'vice_hm_academics',
                    ],
                ] as $roleData) {
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
         * Secondary institutions.
         */
        Institution::where('education_level', 'secondary')
            ->each(function (Institution $institution) use ($lessonReview) {
                foreach ([
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
                ] as $roleData) {
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
         * Tertiary institutions.
         * Existing HOD roles receive lecture review permission.
         */
        Institution::where('education_level', 'tertiary')
            ->each(function (Institution $institution) use ($lectureReview) {
                Role::where('institution_id', $institution->id)
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
            Role::whereIn('slug', [
                'hm',
                'vice_hm_academics',
                'principal',
                'vice_principal',
                'vice_principal_academics',
            ])->get()->each(function (Role $role) use ($lessonReview) {
                $role->permissions()->detach($lessonReview->id);
                $role->delete();
            });
        }

        if ($lectureReview) {
            Institution::where('education_level', 'tertiary')
                ->each(function (Institution $institution) use ($lectureReview) {
                    Role::where('institution_id', $institution->id)
                        ->where('slug', 'hod')
                        ->each(function (Role $role) use ($lectureReview) {
                            $role->permissions()->detach($lectureReview->id);
                        });
                });
        }
    }
};