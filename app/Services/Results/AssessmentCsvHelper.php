<?php

namespace App\Services\Results;

use App\Models\AssessmentComponent;
use App\Models\AssessmentScore;
use Illuminate\Support\Collection;

/**
 * Shared CSV pieces for the lecturer and subject-teacher score imports.
 *
 * Each score column is headed with the component's name and max, e.g.
 * "Test (max 20)". Matching by that heading (names are unique within a
 * scheme) keeps the file readable for a teacher, and a heading that ends in
 * "(max N)" but matches nothing in the current scheme means the file was made
 * for an older version of the scheme, so it is rejected instead of applied.
 */
class AssessmentCsvHelper
{
    public function headerFor(AssessmentComponent $component): string
    {
        return trim($component->name)." (max {$component->max_score})";
    }

    /**
     * Map CSV column index => scheme component by heading. Headings that end
     * in "(max N)" but don't match exactly one current component are returned
     * separately so the caller can reject a stale file. Columns without that
     * ending (matric/admission number, student_name, anything extra) are
     * ignored.
     *
     * @return array{0: array<int, AssessmentComponent>, 1: array<int, string>} [map, unmatchedScoreHeadings]
     */
    public function mapComponentColumns(array $header, Collection $components): array
    {
        $byHeading = [];

        foreach ($components as $component) {
            $byHeading[$this->normalize($this->headerFor($component))][] = $component;
        }

        $map = [];
        $unmatched = [];

        foreach ($header as $index => $label) {
            $label = trim((string) $label);

            if (! preg_match('/\(max\s+[\d.]+\)\s*$/i', $label)) {
                continue;
            }

            $matches = $byHeading[$this->normalize($label)] ?? [];

            if (count($matches) === 1) {
                $map[$index] = $matches[0];
            } else {
                $unmatched[] = $label;
            }
        }

        return [$map, $unmatched];
    }

    private function normalize(string $heading): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim($heading)));
    }

    /**
     * Blank = leave as is. "A"/"ABS"/"absent" = absent. Otherwise a number
     * between 0 and the component's own max.
     *
     * @return array{blank: bool, absent: bool, value: float|null, error: string|null}
     */
    public function parseCell(string $raw, AssessmentComponent $component): array
    {
        $raw = trim($raw);

        if ($raw === '') {
            return ['blank' => true, 'absent' => false, 'value' => null, 'error' => null];
        }

        if (in_array(strtolower($raw), ['a', 'abs', 'absent'], true)) {
            return ['blank' => false, 'absent' => true, 'value' => null, 'error' => null];
        }

        if (! is_numeric($raw)) {
            return ['blank' => false, 'absent' => false, 'value' => null, 'error' => "\"{$raw}\" is not a number"];
        }

        $value = (float) $raw;

        if ($value < 0 || $value > $component->max_score) {
            return [
                'blank' => false,
                'absent' => false,
                'value' => null,
                'error' => $this->formatNumber($value)." is outside 0 to {$component->max_score}",
            ];
        }

        return ['blank' => false, 'absent' => false, 'value' => $value, 'error' => null];
    }

    /** What a saved score looks like in a CSV cell: "" (none), "A", or "7.5". */
    public function describe(?AssessmentScore $score): string
    {
        if (! $score) {
            return '';
        }

        if ($score->is_absent) {
            return 'A';
        }

        return $score->score === null ? '' : $this->formatNumber((float) $score->score);
    }

    public function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
