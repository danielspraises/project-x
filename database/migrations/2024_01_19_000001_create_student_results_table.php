<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            $table->foreignId('academic_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('term_id')->constrained()->restrictOnDelete();

            // Secondary/Primary context.
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('arm_id')->nullable()->constrained('arms')->nullOnDelete();
            $table->foreignId('subject_offering_id')->nullable()->constrained('subject_offerings')->nullOnDelete();

            // Tertiary context.
            $table->foreignId('course_registration_id')->nullable()->constrained('course_registrations')->nullOnDelete();
            $table->foreignId('course_offering_id')->nullable()->constrained('course_offerings')->nullOnDelete();

            $table->decimal('ca_score', 5, 2)->nullable();
            $table->decimal('exam_score', 5, 2)->nullable();
            $table->decimal('total_score', 5, 2)->nullable();

            $table->string('grade', 20)->nullable();
            $table->decimal('grade_point', 5, 2)->nullable();

            $table->string('status', 20)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('locked_at')->nullable();

            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(
                ['institution_id', 'student_id', 'academic_session_id', 'term_id'],
                'student_results_student_period_idx'
            );

            $table->index(
                ['institution_id', 'subject_offering_id'],
                'student_results_subject_offering_idx'
            );

            $table->index(
                ['institution_id', 'course_registration_id'],
                'student_results_course_registration_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_results');
    }
};
