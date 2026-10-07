<?php

namespace App\Support\Learning;

use InvalidArgumentException;

final class LearningContentKinds
{
    public const STANDARD = 'standard';

    public const LESSON_KINDS = [
        'standard' => 'Standard Lesson',
        'lesson_plan' => 'Lesson Plan',
        'scheme_of_work' => 'Scheme of Work',
        'worksheet' => 'Worksheet',
        'reading_material' => 'Reading Material',
        'continuous_assessment' => 'Continuous Assessment',
        'homework' => 'Homework',
    ];

    public const LECTURE_KINDS = [
        'standard' => 'Standard Lecture',
        'lecture_note' => 'Lecture Note',
        'slide_deck' => 'Slide Deck',
        'lab_manual' => 'Lab Manual',
        'module_material' => 'Module Material',
        'assignment' => 'Assignment',
        'tma' => 'TMA',
    ];

    public static function forType(string $contentType): array
    {
        return match ($contentType) {
            'lesson' => self::LESSON_KINDS,
            'lecture' => self::LECTURE_KINDS,
            default => throw new InvalidArgumentException("Unsupported learning content type: {$contentType}"),
        };
    }

    public static function exists(string $contentType, string $contentKind): bool
    {
        return array_key_exists($contentKind, self::forType($contentType));
    }

    public static function label(string $contentType, string $contentKind): string
    {
        return self::forType($contentType)[$contentKind] ?? $contentKind;
    }
}
