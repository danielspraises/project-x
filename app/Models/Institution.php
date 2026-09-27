<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

use App\Models\InstitutionReportCardTemplate;
use App\Models\InstitutionTranscriptTemplate;


use App\Models\Subject;

class Institution extends Model
{
    protected $fillable = ['name', 'slug', 'logo_path', 'type', 'education_level', 'ownership', 'status'];

    public function settings(): HasMany
    {
        return $this->hasMany(InstitutionSetting::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function billingConfig(): HasOne
    {
        return $this->hasOne(InstitutionBillingConfig::class);
    }

    public function billingChangeRequests(): HasMany
    {
        return $this->hasMany(BillingChangeRequest::class);
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'institution_features')
            ->withPivot('enabled', 'changed_by', 'changed_at')
            ->withTimestamps();
    }

    public function hasFeature(string $key): bool
    {
        return $this->features()
            ->where('key', $key)
            ->wherePivot('enabled', true)
            ->exists();
    }

    public function categoryLabel(): string
    {
        return match ($this->type) {
            'university' => 'University',
            'polytechnic' => 'Polytechnic',
            'college_of_education' => 'College of Education',
            'monotechnic' => 'Monotechnic',
            'secondary_school' => 'Secondary School',
            'primary_school' => 'Primary School',
            default => '—',
        };
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings()->where('key', $key)->value('value') ?? $default;
    }

    public function setSetting(string $key, mixed $value): void
    {
        $this->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public function branding(): array
    {
        return $this->setting('branding', [
            'primary_color' => '#5b5ff5',
            'secondary_color' => '#7c3aed',
        ]);
    }

    public function themeDefaults(): array
    {
        $branding = $this->branding();

        return [
            'light_primary' => $branding['light_primary'] ?? $branding['primary_color'] ?? '#5b5ff5',
            'light_secondary' => $branding['light_secondary'] ?? $branding['secondary_color'] ?? '#7c3aed',
            'dark_primary' => $branding['dark_primary'] ?? $branding['primary_color'] ?? '#8b8ff7',
            'dark_secondary' => $branding['dark_secondary'] ?? $branding['secondary_color'] ?? '#9b6cff',
        ];
    }

    public function theme(): array
    {
        $defaults = $this->themeDefaults();
        $override = $this->setting('theme_colors', []);

        return [
            'light_primary' => $override['light_primary'] ?? $defaults['light_primary'],
            'light_secondary' => $override['light_secondary'] ?? $defaults['light_secondary'],
            'dark_primary' => $override['dark_primary'] ?? $defaults['dark_primary'],
            'dark_secondary' => $override['dark_secondary'] ?? $defaults['dark_secondary'],
        ];
    }

    public function themeOverrides(): array
    {
        return $this->setting('theme_colors', []);
    }

    /**
     * Assessment weighting used by the result calculation foundation.
     * The values are percentages and must total 100.
     */
    public function assessmentSettings(): array
    {
        return $this->setting('assessment_settings', [
            'ca_weight' => 40,
            'exam_weight' => 60,
        ]);
    }

    /**
     * Result grading configuration.
     *
     * Primary and secondary education use score -> grade only.
     * Tertiary education additionally uses grade points for GPA/CGPA.
     */
    public function gradingSettings(): array
    {
        $isTertiary = $this->education_level === 'tertiary';

        return $this->setting('grading_settings', [
            'gpa_scale' => $isTertiary ? '5' : 'none',
        ]) + [
            'gpa_scale' => $isTertiary ? '5' : 'none',
        ];
    }

    public function gradingScale(): array
    {
        $isTertiary = $this->education_level === 'tertiary';

        return $this->setting('grading_scale', $isTertiary
            ? [
                ['grade' => 'A', 'min_score' => 70, 'max_score' => 100, 'grade_point' => 5],
                ['grade' => 'B', 'min_score' => 60, 'max_score' => 69, 'grade_point' => 4],
                ['grade' => 'C', 'min_score' => 50, 'max_score' => 59, 'grade_point' => 3],
                ['grade' => 'D', 'min_score' => 45, 'max_score' => 49, 'grade_point' => 2],
                ['grade' => 'E', 'min_score' => 40, 'max_score' => 44, 'grade_point' => 1],
                ['grade' => 'F', 'min_score' => 0, 'max_score' => 39, 'grade_point' => 0],
            ]
            : [
                ['grade' => 'A', 'min_score' => 70, 'max_score' => 100, 'grade_point' => null],
                ['grade' => 'B', 'min_score' => 60, 'max_score' => 69, 'grade_point' => null],
                ['grade' => 'C', 'min_score' => 50, 'max_score' => 59, 'grade_point' => null],
                ['grade' => 'D', 'min_score' => 45, 'max_score' => 49, 'grade_point' => null],
                ['grade' => 'E', 'min_score' => 40, 'max_score' => 44, 'grade_point' => null],
                ['grade' => 'F', 'min_score' => 0, 'max_score' => 39, 'grade_point' => null],
            ]
        );
    }

    public function periodLabel(): string
    {
        $stored = $this->setting('period_label');

        if ($stored === 'term') {
            return 'Term';
        }
        if ($stored === 'semester') {
            return 'Semester';
        }

        return $this->education_level === 'tertiary' ? 'Semester' : 'Term';
    }

    public function periodLabelValue(): string
    {
        return $this->setting('period_label') ?? ($this->education_level === 'tertiary' ? 'semester' : 'term');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    // Institution Report Card  and Transcript Template
    public function reportCardTemplate(): HasOne
    {
        return $this->hasOne(InstitutionReportCardTemplate::class);
    }

    public function transcriptTemplate(): HasOne
    {
        return $this->hasOne(InstitutionTranscriptTemplate::class);
    }
}
