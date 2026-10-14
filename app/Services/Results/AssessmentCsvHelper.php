<?php

namespace App\Services\Results;

use App\Models\AssessmentComponent;
use App\Models\AssessmentScore;
use Illuminate\Support\Collection;

/**
 * Shared CSV pieces for the lecturer and subject-teacher score imports.
 *
 * Each score column is tagged with its component id, e.g.
 * "CA 1 (max 10) [c12]". Matching by id (not by name) means a renamed or
 * duplicated component name can't send a score to the wrong column, and a
 * file made for an older version of the scheme is detected and rejected
 * instead of being applied to the wrong components.
 */
class AssessmentCsvHelper
{
    public function headerFor(AssessmentComponent $component): string
    {
        return "{$component->name} (max {$component->max_score}) [c{$component->id}]";
    }

    /**
     * Map CSV column index => scheme component, using the [c<id>] tag in the
     * heading. Headings tagged with an id the scheme no longer has are
     * returned separately so the caller can reject a stale file.
     *
     * @return array{0: array<int, AssessmentComponent>, 1: array<int, string>} [map, unknownHeadings]
     */
    public function mapComponentColumns(array $header, Collection $components): array
    {
        $byId = $components->keyBy('id');
        $map = [];
        $unknown = [];

        foreach ($header as $index => $label) {
            if (preg_match('/\[c(\d+)\]\s*$/i', (string) $label, $m)) {
                $component = $byId->get((int) $m[1]);

                if ($component) {
                    $map[$index] = $component;
                } else {
                    $unknown[] = trim((string) $label);
                }
            }
        }

        return [$map, $unknown];
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
