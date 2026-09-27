<?php

use App\Models\Feature;
use App\Models\Institution;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $features = [
        [
            'key' => 'results.report_cards',
            'name' => 'Report Cards',
            'description' => 'Generate institutional student report cards from approved/published results.',
            'category' => 'results',
            'is_core' => false,
        ],
        [
            'key' => 'results.transcripts',
            'name' => 'Transcripts',
            'description' => 'Generate tertiary student academic transcripts from approved/published results.',
            'category' => 'results',
            'is_core' => false,
        ],
        [
            'key' => 'results.positions',
            'name' => 'Result Positions',
            'description' => 'Display calculated class, arm, subject and overall positions where configured.',
            'category' => 'results',
            'is_core' => false,
        ],
    ];

    public function up(): void
    {
        foreach ($this->features as $definition) {
            $feature = Feature::query()->updateOrCreate(
                ['key' => $definition['key']],
                $definition
            );

            // These are deliberately disabled for existing institutions.
            // Super Admin must explicitly activate them.
            Institution::query()
                ->select('id')
                ->chunkById(100, function ($institutions) use ($feature) {
                    foreach ($institutions as $institution) {
                        DB::table('institution_features')->updateOrInsert(
                            [
                                'institution_id' => $institution->id,
                                'feature_id' => $feature->id,
                            ],
                            [
                                'enabled' => false,
                                'changed_by' => null,
                                'changed_at' => null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                });
        }
    }

    public function down(): void
    {
        $keys = collect($this->features)->pluck('key');

        $featureIds = Feature::query()
            ->whereIn('key', $keys)
            ->pluck('id');

        DB::table('institution_features')
            ->whereIn('feature_id', $featureIds)
            ->delete();

        Feature::query()
            ->whereIn('id', $featureIds)
            ->delete();
    }
};
