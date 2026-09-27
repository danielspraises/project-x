<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_gpa_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academic_session_id');
            $table->unsignedBigInteger('term_id');
            $table->decimal('total_credit_units', 8, 2);
            $table->decimal('total_quality_points', 10, 2);
            $table->decimal('gpa', 6, 2);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['institution_id', 'student_id', 'academic_session_id', 'term_id'],
                'student_gpa_summary_scope_unique'
            );
            $table->index(
                ['institution_id', 'academic_session_id', 'term_id'],
                'student_gpa_summary_context_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_gpa_summaries');
    }
};
