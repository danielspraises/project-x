<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InstitutionSettingsController extends Controller
{
    public function edit(): View
    {
        abort_unless(Auth::user()->hasPermission('institution.setup'), 403);

        $institution = Auth::user()->institution;
        $branding = $institution->branding();
        $themeDefaults = $institution->themeDefaults();
        $themeOverrides = $institution->themeOverrides();
        $assessmentSettings = $institution->assessmentSettings();
        $gradingSettings = $institution->gradingSettings();
        $gradingScale = $institution->gradingScale();

        return view('ict-admin.settings.edit', compact(
            'institution',
            'branding',
            'themeDefaults',
            'themeOverrides',
            'assessmentSettings',
            'gradingScale',
            'gradingSettings'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->hasPermission('institution.setup'), 403);

        $institution = Auth::user()->institution;
        $isTertiary = $institution->education_level === 'tertiary';

        $validated = $request->validate([
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'light_primary' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'light_secondary' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'dark_primary' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'dark_secondary' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'use_default_light_primary' => ['nullable', 'boolean'],
            'use_default_light_secondary' => ['nullable', 'boolean'],
            'use_default_dark_primary' => ['nullable', 'boolean'],
            'use_default_dark_secondary' => ['nullable', 'boolean'],
            'period_label' => ['required', 'in:semester,term'],

            'ca_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'exam_weight' => ['required', 'integer', 'min:0', 'max:100'],

            'grading_scale' => ['required', 'array', 'min:2', 'max:10'],
            'grading_scale.*.grade' => ['required', 'string', 'max:10'],
            'grading_scale.*.min_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'grading_scale.*.max_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'gpa_scale' => [$isTertiary ? 'required' : 'nullable', 'in:none,4,5,custom'],
            'custom_gpa_max' => ['nullable', 'numeric', 'gt:0', 'max:10'],
            'grading_scale.*.grade_point' => ['nullable', 'numeric', 'min:0', 'max:10'],
        ]);

        if (($validated['ca_weight'] + $validated['exam_weight']) !== 100) {
            return back()
                ->withInput()
                ->withErrors(['ca_weight' => 'CA weight and Exam weight must total exactly 100%.']);
        }

        foreach ($validated['grading_scale'] as $index => $row) {
            if ((float) $row['min_score'] > (float) $row['max_score']) {
                return back()
                    ->withInput()
                    ->withErrors(["grading_scale.$index.min_score" => 'Minimum score cannot be greater than maximum score.']);
            }
        }

        if ($isTertiary && $validated['gpa_scale'] === 'custom') {
            if (($validated['custom_gpa_max'] ?? null) === null) {
                return back()->withInput()->withErrors(['custom_gpa_max' => 'Enter the maximum GPA for the custom scale.']);
            }

            $customMax = (float) $validated['custom_gpa_max'];
            foreach ($validated['grading_scale'] as $index => $row) {
                if (($row['grade_point'] ?? null) === null || (float) $row['grade_point'] > $customMax) {
                    return back()->withInput()->withErrors(["grading_scale.$index.grade_point" => 'Grade points must be present and cannot exceed the custom maximum GPA.']);
                }
            }
        }

        if ($isTertiary && $validated['gpa_scale'] === '4') {
            foreach ($validated['grading_scale'] as $index => $row) {
                if (($row['grade_point'] ?? null) === null || (float) $row['grade_point'] > 4) {
                    return back()
                        ->withInput()
                        ->withErrors(["grading_scale.$index.grade_point" => 'Grade points must be present and cannot exceed 4 on a 4-point GPA scale.']);
                }
            }
        }

        if ($isTertiary && $validated['gpa_scale'] === '5') {
            foreach ($validated['grading_scale'] as $index => $row) {
                if (($row['grade_point'] ?? null) === null || (float) $row['grade_point'] > 5) {
                    return back()
                        ->withInput()
                        ->withErrors(["grading_scale.$index.grade_point" => 'Grade points must be present and cannot exceed 5 on a 5-point GPA scale.']);
                }
            }
        }

        $branding = $institution->branding();
        if (array_key_exists('primary_color', $validated) || array_key_exists('secondary_color', $validated)) {
            $institution->setSetting('branding', [
                'primary_color' => $validated['primary_color'] ?? ($branding['primary_color'] ?? '#5b5ff5'),
                'secondary_color' => $validated['secondary_color'] ?? ($branding['secondary_color'] ?? null),
            ]);
        }

        $institution->setSetting('period_label', $validated['period_label']);

        $themeColors = $institution->themeOverrides();

        foreach (['light_primary', 'light_secondary', 'dark_primary', 'dark_secondary'] as $key) {
            $useDefault = (bool) ($validated['use_default_'.$key] ?? false);

            if ($useDefault || empty($validated[$key])) {
                unset($themeColors[$key]);
            } else {
                $themeColors[$key] = $validated[$key];
            }
        }

        $institution->setSetting('theme_colors', $themeColors);

        $institution->setSetting('assessment_settings', [
            'ca_weight' => (int) $validated['ca_weight'],
            'exam_weight' => (int) $validated['exam_weight'],
        ]);

        $gpaScale = $isTertiary ? ($validated['gpa_scale'] ?? 'none') : 'none';

        $institution->setSetting('grading_settings', [
            'gpa_scale' => $gpaScale,
            'custom_gpa_max' => $gpaScale === 'custom' ? (float) ($validated['custom_gpa_max'] ?? 0) : null,
        ]);

        $institution->setSetting('grading_scale', array_values(array_map(
            fn (array $row) => [
                'grade' => trim($row['grade']),
                'min_score' => (float) $row['min_score'],
                'max_score' => (float) $row['max_score'],
                'grade_point' => $isTertiary && $gpaScale !== 'none'
                    ? (float) $row['grade_point']
                    : null,
            ],
            $validated['grading_scale']
        )));

        return redirect()->route('ict-admin.settings.edit')->with('success', 'Settings updated.');
    }
}