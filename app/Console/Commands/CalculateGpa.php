<?php

namespace App\Console\Commands;

use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Term;
use App\Services\Results\GpaCalculationService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class CalculateGpa extends Command
{
    protected $signature = 'gpa:calculate
        {institution_id : Institution ID}
        {academic_session_id : Academic session ID}
        {term_id : Term ID}
        {--student= : Calculate one student only}';

    protected $description = 'Calculate tertiary term GPA for one student or all registered students in a term.';

    public function handle(GpaCalculationService $service): int
    {
        $institution = Institution::find($this->argument('institution_id'));
        $session = AcademicSession::where('institution_id', $this->argument('institution_id'))->find($this->argument('academic_session_id'));
        $term = Term::where('institution_id', $this->argument('institution_id'))->find($this->argument('term_id'));

        if (! $institution || ! $session || ! $term) {
            $this->error('Institution, academic session, or term could not be found in the same institution.');
            return self::FAILURE;
        }

        try {
            if ($this->option('student')) {
                $student = Student::where('institution_id', $institution->id)->find($this->option('student'));

                if (! $student) {
                    $this->error('Student not found in the selected institution.');
                    return self::FAILURE;
                }

                $summary = $service->calculateForStudent($institution, $student, $session, $term);
                $this->info("GPA: {$summary->gpa} / {$this->maximum($institution)} | Credits: {$summary->total_credit_units} | Quality points: {$summary->total_quality_points}");
                return self::SUCCESS;
            }

            $result = $service->calculateForTerm($institution, $session, $term);
            $this->info('Calculated: ' . count($result['calculated']));
            $this->line('Failed: ' . count($result['failed']));

            foreach ($result['failed'] as $failure) {
                $this->warn(($failure['student_name'] ?? ('Student #' . $failure['student_id'])) . ': ' . $failure['message']);
            }

            return $result['failed'] === [] ? self::SUCCESS : self::FAILURE;
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) $this->error($message);
            }
            return self::FAILURE;
        }
    }

    private function maximum(Institution $institution): string
    {
        $settings = $institution->gradingSettings();
        return match ($settings['gpa_scale'] ?? '5') {
            '4' => '4.00',
            'custom' => number_format((float) ($settings['custom_gpa_max'] ?? 0), 2),
            default => '5.00',
        };
    }
}
