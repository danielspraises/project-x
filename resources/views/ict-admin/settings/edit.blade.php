<x-app-layout>
<x-slot name="header">
    <div>
        <div class="cx-kicker">Institution control</div>
        <div class="font-display text-base font-semibold text-[var(--ink)]">Institution Settings</div>
    </div>
</x-slot>

<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-[1100px] space-y-7">
        @if(session('success'))
            <div class="ui-note text-sm">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl border border-rose-400/30 bg-rose-400/10 px-5 py-4 text-sm text-rose-600 dark:text-rose-300">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $isTertiary = $institution->education_level === 'tertiary';
            $selectedGpaScale = old('gpa_scale', $gradingSettings['gpa_scale'] ?? ($isTertiary ? '5' : 'none'));
            $customGpaMax = old('custom_gpa_max', $gradingSettings['custom_gpa_max'] ?? '');
        @endphp

        <section class="cx-panel cx-grid rounded-[30px] p-7 sm:p-9">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="cx-kicker">Visual identity</div>
                    <h1 class="mt-2 font-display text-3xl font-semibold">Shape the institution experience.</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">
                        Choose separate colours for light and dark mode. These settings override the Super Admin defaults only where you customise them.
                    </p>
                </div>
                <div class="ui-badge">ICT Admin</div>
            </div>

            <form method="POST" action="{{ route('ict-admin.settings.update') }}" class="mt-8 space-y-8">
                @csrf
                @method('PUT')

                <div class="grid gap-5 lg:grid-cols-2">
                    @foreach([
                        ['light_primary','Light primary','Primary accent for light mode'],
                        ['light_secondary','Light secondary','Secondary accent for light mode'],
                        ['dark_primary','Dark primary','Primary accent for dark mode'],
                        ['dark_secondary','Dark secondary','Secondary accent for dark mode'],
                    ] as [$key, $label, $help])
                        @php($isOverride = array_key_exists($key, $themeOverrides))
                        <div class="ui-color-field">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <label class="ui-label" for="{{ $key }}">{{ $label }}</label>
                                    <p class="ui-muted mt-1 text-xs">{{ $help }}</p>
                                </div>
                                <span class="ui-badge">{{ $isOverride ? 'Custom' : 'Super Admin default' }}</span>
                            </div>
                            <div class="mt-4 flex items-center gap-3">
                                <input id="{{ $key }}" type="color" name="{{ $key }}"
                                       value="{{ old($key, $themeOverrides[$key] ?? $themeDefaults[$key]) }}"
                                       class="ui-color-picker" {{ $isOverride ? '' : 'disabled' }}>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-semibold text-[var(--ink)]">
                                        {{ $isOverride ? $themeOverrides[$key] : $themeDefaults[$key] }}
                                    </div>
                                    <label class="mt-2 flex items-center gap-2 text-xs text-[var(--muted)]">
                                        <input type="checkbox" name="use_default_{{ $key }}" value="1"
                                               {{ $isOverride ? '' : 'checked' }}
                                               onchange="document.getElementById('{{ $key }}').disabled=this.checked">
                                        Use Super Admin default
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="ui-panel p-5">
                    <div class="ui-eyebrow">Result management Â· Foundation</div>
                    <h2 class="mt-2 font-display text-2xl font-semibold">Assessment &amp; grading</h2>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-[var(--muted)]">
                        Configure the institution-wide assessment weighting and the score-to-grade scale used by the result engine.
                        This is configuration only; result entry, calculation, approval and publication are introduced in later batches.
                    </p>

                    <div class="mt-7 grid gap-5 md:grid-cols-2">
                        <div class="ui-panel p-5">
                            <label class="ui-label" for="ca_weight">Continuous Assessment weight (%)</label>
                            <input id="ca_weight" name="ca_weight" type="number" min="0" max="100"
                                   value="{{ old('ca_weight', $assessmentSettings['ca_weight']) }}"
                                   class="cx-input mt-2 w-full" required>
                            <p class="ui-muted mt-2 text-xs">Contribution of tests, assignments, practicals or other CA components.</p>
                        </div>

                        <div class="ui-panel p-5">
                            <label class="ui-label" for="exam_weight">Examination weight (%)</label>
                            <input id="exam_weight" name="exam_weight" type="number" min="0" max="100"
                                   value="{{ old('exam_weight', $assessmentSettings['exam_weight']) }}"
                                   class="cx-input mt-2 w-full" required>
                            <p class="ui-muted mt-2 text-xs">CA and Examination weights must total exactly 100%. A new weighting applies to every course and subject that has no scores yet; ones that already have scores keep the split they were scored under.</p>
                        </div>
                    </div>

                    @if($isTertiary)
                        <div class="ui-panel mt-6 p-5">
                            <div class="ui-eyebrow">GPA configuration</div>
                            <h3 class="mt-2 font-display text-lg font-semibold">Tertiary GPA scale</h3>

                            <div class="mt-5 max-w-md">
                                <label class="ui-label" for="gpa_scale">GPA scale</label>
                                <select id="gpa_scale" name="gpa_scale" class="cx-input mt-2 w-full" required>
                                    <option value="5" @selected($selectedGpaScale === '5')>5-point GPA</option>
                                    <option value="4" @selected($selectedGpaScale === '4')>4-point GPA</option>
                                    <option value="custom" @selected($selectedGpaScale === 'custom')>Custom GPA scale</option>
                                </select>
                            </div>

                            <div id="custom-gpa-settings" class="mt-5 max-w-md {{ $selectedGpaScale === 'custom' ? '' : 'hidden' }}">
                                <label class="ui-label" for="custom_gpa_max">Maximum GPA</label>
                                <input id="custom_gpa_max" name="custom_gpa_max" type="number"
                                       min="0.01" max="10" step="0.01"
                                       value="{{ $customGpaMax }}"
                                       class="cx-input mt-2 w-full">
                                <p class="ui-muted mt-2 text-xs">
                                    Define the highest grade-point value permitted on your custom scale.
                                </p>
                            </div>
                        </div>
                    @endif

                    <div class="ui-panel mt-6 overflow-hidden">
                        <div class="p-5">
                            <div class="ui-eyebrow">Grade bands</div>
                            <h3 class="mt-2 font-display text-lg font-semibold">
                                {{ $isTertiary ? 'Score â†’ Grade â†’ Grade Point' : 'Score â†’ Grade' }}
                            </h3>
                            <p class="ui-muted mt-2 text-xs leading-5">
                                Configure the score ranges used by the result calculation engine.
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-y border-[var(--line)] text-left text-xs uppercase tracking-wider text-[var(--muted)]">
                                        <th class="px-5 py-3">Grade</th>
                                        <th class="px-5 py-3">Minimum</th>
                                        <th class="px-5 py-3">Maximum</th>
                                        @if($isTertiary)
                                            <th class="px-5 py-3">Grade point</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(old('grading_scale', $gradingScale) as $index => $row)
                                        <tr class="border-b border-[var(--line)] last:border-0">
                                            <td class="px-5 py-3">
                                                <input name="grading_scale[{{ $index }}][grade]"
                                                       value="{{ $row['grade'] }}"
                                                       class="cx-input w-24" required>
                                            </td>
                                            <td class="px-5 py-3">
                                                <input name="grading_scale[{{ $index }}][min_score]"
                                                       type="number" min="0" max="100" step="0.01"
                                                       value="{{ $row['min_score'] }}"
                                                       class="cx-input w-28" required>
                                            </td>
                                            <td class="px-5 py-3">
                                                <input name="grading_scale[{{ $index }}][max_score]"
                                                       type="number" min="0" max="100" step="0.01"
                                                       value="{{ $row['max_score'] }}"
                                                       class="cx-input w-28" required>
                                            </td>
                                            @if($isTertiary)
                                                <td class="px-5 py-3">
                                                    <input name="grading_scale[{{ $index }}][grade_point]"
                                                           type="number" min="0" max="10" step="0.01"
                                                           value="{{ $row['grade_point'] ?? '' }}"
                                                           class="cx-input w-28" required>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="ui-panel p-5">
                    <div class="ui-eyebrow">Academic period</div>
                    <h2 class="mt-2 font-display text-xl font-semibold">Academic period type</h2>
                    <div class="mt-5 max-w-md">
                        <label class="ui-label" for="period_label">Academic period type</label>
                        <select id="period_label" name="period_label" class="cx-input mt-2 w-full" required>
                            <option value="semester" @selected(old('period_label', $institution->periodLabelValue()) === 'semester')>Semester</option>
                            <option value="term" @selected(old('period_label', $institution->periodLabelValue()) === 'term')>Term</option>
                        </select>
                        <p class="ui-muted mt-2 text-xs leading-5">Controls terminology used throughout the academic modules.</p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="cx-button cx-button-primary rounded-2xl px-6 py-3 text-sm font-bold">
                        Save institution settings
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>

@if($isTertiary)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const scale = document.getElementById('gpa_scale');
    const customSettings = document.getElementById('custom-gpa-settings');
    const customMax = document.getElementById('custom_gpa_max');

    if (!scale) {
        return;
    }

    const standard = {
        '5': { max: 5, points: [5, 4, 3, 2, 1, 0] },
        '4': { max: 4, points: [4, 3, 2, 1, 0, 0] }
    };

    function applyScale() {
        const preset = standard[scale.value];

        if (customSettings) {
            customSettings.classList.toggle('hidden', scale.value !== 'custom');
        }

        if (!preset) {
            return;
        }

        if (customMax) {
            customMax.value = preset.max;
        }

        document.querySelectorAll('input[name$="[grade_point]"]').forEach(function (input, index) {
            if (preset.points[index] !== undefined) {
                input.value = preset.points[index];
            }
        });
    }

    scale.addEventListener('change', applyScale);
});
</script>
@endif

</x-app-layout>
