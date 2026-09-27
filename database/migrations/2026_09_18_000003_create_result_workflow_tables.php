<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_offering_id')->nullable()->constrained('course_offerings')->nullOnDelete();
            $table->foreignId('subject_offering_id')->nullable()->constrained('subject_offerings')->nullOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->text('submission_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'academic_session_id', 'term_id'], 'rs_inst_period_idx');
            $table->index(['institution_id', 'status'], 'rs_inst_status_idx');
        });

        Schema::create('result_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('result_submission_id')->constrained('result_submissions')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('level');
            $table->string('decision');
            $table->text('notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'level', 'decision'], 'rv_inst_level_decision_idx');
        });

        Schema::create('student_result_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_result_id')->constrained('student_results')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_type')->default('update');
            $table->json('snapshot');
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['student_result_id', 'version_number'], 'srv_result_version_uq');
            $table->index(['institution_id', 'student_result_id'], 'srv_inst_result_idx');
        });

        Schema::create('result_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_result_id')->constrained('student_results')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('requested');
            $table->text('reason');
            $table->text('resolution_note')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'status'], 'rc_inst_status_idx');
            $table->index(['institution_id', 'student_result_id'], 'rc_inst_result_idx');
        });

        Schema::create('result_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_result_id')->nullable()->constrained('student_results')->nullOnDelete();
            $table->foreignId('result_submission_id')->nullable()->constrained('result_submissions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('reason')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'action'], 'ral_inst_action_idx');
            $table->index(['institution_id', 'created_at'], 'ral_inst_created_idx');
        });

        Schema::create('result_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('result_submission_id')->nullable()->constrained('result_submissions')->nullOnDelete();
            $table->string('type');
            $table->string('severity')->default('warning');
            $table->string('title');
            $table->text('message');
            $table->boolean('resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'resolved'], 'ra_inst_resolved_idx');
            $table->index(['institution_id', 'type'], 'ra_inst_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_alerts');
        Schema::dropIfExists('result_audit_logs');
        Schema::dropIfExists('result_corrections');
        Schema::dropIfExists('student_result_versions');
        Schema::dropIfExists('result_verifications');
        Schema::dropIfExists('result_submissions');
    }
};
