<?php

namespace App\Services\Learning;

use App\Models\Institution;
use Illuminate\Validation\ValidationException;

class LearningContentKindService
{
    public const PRIMARY_SECONDARY = [
        'standard' => 'Standard Lesson',
        'lesson_plan' => 'Lesson Plan',
        'scheme_of_work' => 'Scheme of Work',
        'worksheet' => 'Worksheet',
        'reading_material' => 'Reading Material',
        'continuous_assessment' => 'Continuous Assessment',
        'homework' => 'Homework',
    ];

    public const TERTIARY = [
        'standard' => 'Standard Lecture',
        'lecture_note' => 'Lecture Note',
        'slide_deck' => 'Slide Deck',
        'lab_manual' => 'Lab Manual',
        'module_material' => 'Module Material',
        'assignment' => 'Assignment',
        'tma' => 'TMA',
    ];

    public static function options(?Institution $institution, string $contentType): array
    {
        if ($contentType === 'lesson') {
            return self::PRIMARY_SECONDARY;
        }

        if ($contentType === 'lecture') {
            return self::TERTIARY;
        }

        return ['standard' => 'Standard'];
    }

    public static function validate(?Institution $institution, string $contentType, string $contentKind): void
    {
        $allowed = self::options($institution, $contentType);

        if (! array_key_exists($contentKind, $allowed)) {
            throw ValidationException::withMessages([
                'content_kind' => 'The selected content type is not available for this learning content.',
            ]);
        }

        if ($contentType === 'lesson' && $institution?->education_level === 'tertiary') {
            throw ValidationException::withMessages([
                'content_type' => 'Lessons are only available to primary and secondary institutions.',
            ]);
        }

        if ($contentType === 'lecture' && $institution?->education_level !== 'tertiary') {
            throw ValidationException::withMessages([
                'content_type' => 'Lectures are only available to tertiary institutions.',
            ]);
        }
    }
}
