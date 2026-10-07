<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_component_id')->constrained()->cascadeOnDelete();

            // Ties into the existing student_results row so ca_score/exam_score/
            // total_score on that row stay the computed summary of these —
            // submission, review, the Control Room and grading keep reading
            // student_results unchanged.
            $table->foreignId('student_result_id')->constrained('student_results')->cascadeOnDelete();

            $table->decimal('score', 5, 2)->nullable();

            // Counts as 0 toward the total, but — unlike a blank score — lets
            // that component be considered "entered" so the overall total can
            // still complete even though this one student missed it.
            $table->boolean('is_absent')->default(false);

            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['assessment_component_id', 'student_result_id'], 'as_component_result_uq');
            $table->index(['institution_id', 'student_result_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_scores');
    }
};
