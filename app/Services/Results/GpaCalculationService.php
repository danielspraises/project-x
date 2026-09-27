<?php

namespace App\Services\Results;

use App\Models\AcademicSession;
use App\Models\CourseRegistration;
use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentGpaSummary;
use App\Models\StudentResult;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GpaCalculationService
{
    public function calculateForStudent(
        Institution $institution,
        Student $student,
        AcademicSession $session,
        Term $term,
    ): StudentGpaSummary {
        $this->ensureTertiary($institution);
        $this->ensureContext($institution, $student, $session, $term);

        return DB::transaction(function () use ($institution, $student, $session, $term) {
            $registrations = CourseRegistration::query()
                ->with('courseOffering.course')
                ->where('institution_id', $institution->id)
                ->where('student_id', $student->id)
                ->whereHas('courseOffering', fn ($query) => $query->where('term_id', $term->id))
                ->get();

            if ($registrations->isEmpty()) {
                throw ValidationException::withMessages([
                    'gpa' => 'The student has no course registrations for the selected academic period.',
                ]);
            }

            $results = StudentResult::query()
                ->where('institution_id', $institution->id)
                ->where('student_id', $student->id)
                ->where('academic_session_id', $session->id)
                ->where('term_id', $term->id)
                ->whereNotNull('course_registration_id')
                ->get()
                ->keyBy('course_registration_id');

            $totalCredits = 0.0;
            $totalQualityPoints = 0.0;
            $errors = [];

            foreach ($registrations as $registration) {
                $result = $results->get($registration->id);
                $course = $registration->courseOffering?->course;
                $courseLabel = $course?->code ?: 'Course #' . $registration->course_offering_id;

                if (! $result) {
                    $errors["course_{$registration->id}"][] = "No result has been entered for {$courseLabel}.";
                    continue;
                }

                if ($result->total_score === null || ! is_numeric($result->total_score)) {
                    $errors["course_{$registration->id}"][] = "{$courseLabel} does not have a valid total score.";
                    continue;
                }

                $creditUnit = $course?->credit_unit;
                if ($creditUnit === null || ! is_numeric($creditUnit) || (float) $creditUnit <= 0) {
                    $errors["course_{$registration->id}"][] = "{$courseLabel} does not have a valid credit unit.";
                    continue;
                }

                try {
                    $grade = $this->resolveGrade($institution, (float) $result->total_score);
                    $gradePoint = $this->resolveGradePoint($institution, $grade);
                } catch (\Throwable $exception) {
                    $errors["course_{$registration->id}"][] = $exception->getMessage();
                    continue;
                }

                if ($result->status === 'locked' && ($result->grade !== $grade || (float) ($result->grade_point ?? -1) !== $gradePoint)) {
                    $errors["course_{$registration->id}"][] = "{$courseLabel} is locked and its stored grade does not match the configured grading scale.";
                    continue;
                }

                if ($result->status !== 'locked') {
                    $result->grade = $grade;
                    $result->grade_point = $gradePoint;
                    $result->save();
                }

                $credits = (float) $creditUnit;
                $totalCredits += $credits;
                $totalQualityPoints += $credits * $gradePoint;
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            if ($totalCredits <= 0) {
                throw ValidationException::withMessages([
                    'gpa' => 'GPA cannot be calculated because the total registered credit units are zero.',
                ]);
            }

            $gpa = round($totalQualityPoints / $totalCredits, 2);
            $maximum = $this->gpaMaximum($institution);

            if ($gpa < 0 || $gpa > $maximum + 0.0001) {
                throw ValidationException::withMessages([
                    'gpa' => 'The calculated GPA falls outside the configured GPA scale.',
                ]);
            }

            return StudentGpaSummary::updateOrCreate(
                [
                    'institution_id' => $institution->id,
                    'student_id' => $student->id,
                    'academic_session_id' => $session->id,
                    'term_id' => $term->id,
                ],
                [
                    'total_credit_units' => round($totalCredits, 2),
                    'total_quality_points' => round($totalQualityPoints, 2),
                    'gpa' => $gpa,
                    'calculated_at' => now(),
                ]
            );
        });
    }

    public function calculateForTerm(
        Institution $institution,
        AcademicSession $session,
        Term $term,
    ): array {
        $this->ensureTertiary($institution);
        $this->ensureContext($institution, null, $session, $term);

        $studentIds = CourseRegistration::query()
            ->where('institution_id', $institution->id)
            ->whereHas('courseOffering', fn ($query) => $query->where('term_id', $term->id))
            ->distinct()
            ->pluck('student_id');

        $calculated = [];
        $failed = [];

        foreach ($studentIds as $studentId) {
            $student = Student::where('institution_id', $institution->id)->find($studentId);

            if (! $student) {
                $failed[] = ['student_id' => $studentId, 'message' => 'Student record not found.'];
                continue;
            }

            try {
                $summary = $this->calculateForStudent($institution, $student, $session, $term);
                $calculated[] = $summary;
            } catch (ValidationException $exception) {
                $failed[] = [
                    'student_id' => $student->id,
                    'student_name' => trim($student->first_name . ' ' . $student->last_name),
                    'message' => collect($exception->errors())->flatten()->implode(' '),
                ];
            }
        }

        return compact('calculated', 'failed');
    }

    private function resolveGrade(Institution $institution, float $score): string
    {
        foreach ($institution->gradingScale() as $row) {
            if ($score >= (float) $row['min_score'] && $score <= (float) $row['max_score']) {
                return (string) $row['grade'];
            }
        }

        throw new \RuntimeException("No grading band covers the score {$score}.");
    }

    private function resolveGradePoint(Institution $institution, string $grade): float
    {
        $row = collect($institution->gradingScale())
            ->first(fn ($item) => (string) $item['grade'] === $grade);

        if (! $row || $row['grade_point'] === null || ! is_numeric($row['grade_point'])) {
            throw new \RuntimeException("No grade point is configured for grade {$grade}.");
        }

        $gradePoint = (float) $row['grade_point'];
        $maximum = $this->gpaMaximum($institution);

        if ($gradePoint < 0 || $gradePoint > $maximum) {
            throw new \RuntimeException("Grade point for {$grade} exceeds the configured GPA scale.");
        }

        return $gradePoint;
    }

    private function gpaMaximum(Institution $institution): float
    {
        $settings = $institution->gradingSettings();
        $scale = $settings['gpa_scale'] ?? '5';

        return match ($scale) {
            '4' => 4.0,
            '5' => 5.0,
            'custom' => (float) ($settings['custom_gpa_max'] ?? 0),
            default => 0.0,
        };
    }

    private function ensureTertiary(Institution $institution): void
    {
        if ($institution->education_level !== 'tertiary') {
            throw ValidationException::withMessages([
                'gpa' => 'GPA calculation is available only for tertiary institutions.',
            ]);
        }
    }

    private function ensureContext(Institution $institution, ?Student $student, AcademicSession $session, Term $term): void
    {
        if ((int) $session->institution_id !== (int) $institution->id) {
            abort(403);
        }

        if ((int) $term->institution_id !== (int) $institution->id || (int) $term->academic_session_id !== (int) $session->id) {
            throw ValidationException::withMessages([
                'term_id' => 'The selected academic period does not belong to the selected session.',
            ]);
        }

        if ($student && (int) $student->institution_id !== (int) $institution->id) {
            abort(403);
        }
    }
}
