<?php

use App\Models\Feature;
use App\Models\Institution;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * The feature catalog. Add a new row here every time a new module is built —
     * this is the single source of truth Super Admin's toggle screen reads from.
     */
    private const FEATURES = [
        ['key' => 'institution_structure', 'name' => 'Institution Structure', 'description' => 'Faculties/Departments/Programmes or Classes/Arms, depending on institution type.', 'category' => 'Core', 'is_core' => true],
        ['key' => 'students', 'name' => 'Student Management', 'description' => 'Student records, profiles, and bulk import.', 'category' => 'Core', 'is_core' => true],
        ['key' => 'courses', 'name' => 'Course Management', 'description' => 'Courses, course offerings, and course registration (tertiary institutions).', 'category' => 'Academic', 'is_core' => true],
        ['key' => 'bulk_import', 'name' => 'Bulk Import', 'description' => 'CSV bulk upload for students.', 'category' => 'Productivity', 'is_core' => false],
        ['key' => 'custom_roles', 'name' => 'Custom Roles', 'description' => "Create roles beyond the institution's defaults, with custom permission sets.", 'category' => 'Administration', 'is_core' => false],
        ['key' => 'notes', 'name' => 'Notes', 'description' => 'Leave notes on students, courses, and other records.', 'category' => 'Productivity', 'is_core' => false],
    ];

    public function up(): void
    {
        foreach (self::FEATURES as $feature) {
            Feature::firstOrCreate(['key' => $feature['key']], $feature);
        }

        // Backfill: every institution that already exists gets every feature enabled —
        // this is a rollout of a new restriction system, not something that should
        // suddenly take features away from institutions already using them.
        $features = Feature::all();

        Institution::all()->each(function (Institution $institution) use ($features) {
            foreach ($features as $feature) {
                $institution->features()->syncWithoutDetaching([
                    $feature->id => ['enabled' => true, 'changed_at' => now()],
                ]);
            }
        });
    }

    public function down(): void
    {
        Feature::whereIn('key', array_column(self::FEATURES, 'key'))->delete();
    }
};
