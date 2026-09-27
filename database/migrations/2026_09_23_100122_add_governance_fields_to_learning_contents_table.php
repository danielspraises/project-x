<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_contents', function (Blueprint $table) {
            if (!Schema::hasColumn('learning_contents', 'workflow_status')) {
                $table->enum('workflow_status', [
                    'draft',
                    'submitted',
                    'returned',
                    'approved',
                    'published',
                    'archived',
                ])->default('draft')->after('status');
            }

            if (!Schema::hasColumn('learning_contents', 'reviewed_by')) {
                $table->foreignId('reviewed_by')
                    ->nullable()
                    ->after('workflow_status')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('learning_contents', 'reviewed_at')) {
                $table->timestamp('reviewed_at')
                    ->nullable()
                    ->after('reviewed_by');
            }

            if (!Schema::hasColumn('learning_contents', 'review_remarks')) {
                $table->text('review_remarks')
                    ->nullable()
                    ->after('reviewed_at');
            }

            if (!Schema::hasColumn('learning_contents', 'submitted_at')) {
                $table->timestamp('submitted_at')
                    ->nullable()
                    ->after('review_remarks');
            }

            if (!Schema::hasColumn('learning_contents', 'approved_at')) {
                $table->timestamp('approved_at')
                    ->nullable()
                    ->after('submitted_at');
            }
        });

        Schema::table('learning_contents', function (Blueprint $table) {
            $table->index(
                ['institution_id', 'content_type', 'workflow_status'],
                'lc_workflow_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('learning_contents', function (Blueprint $table) {
            if (Schema::hasColumn('learning_contents', 'reviewed_by')) {
                $table->dropForeign(['reviewed_by']);
            }

            if (Schema::hasColumn('learning_contents', 'workflow_status')) {
                $table->dropIndex('lc_workflow_idx');
            }

            $columns = array_filter([
                Schema::hasColumn('learning_contents', 'workflow_status')
                    ? 'workflow_status'
                    : null,

                Schema::hasColumn('learning_contents', 'reviewed_by')
                    ? 'reviewed_by'
                    : null,

                Schema::hasColumn('learning_contents', 'reviewed_at')
                    ? 'reviewed_at'
                    : null,

                Schema::hasColumn('learning_contents', 'review_remarks')
                    ? 'review_remarks'
                    : null,

                Schema::hasColumn('learning_contents', 'submitted_at')
                    ? 'submitted_at'
                    : null,

                Schema::hasColumn('learning_contents', 'approved_at')
                    ? 'approved_at'
                    : null,
            ]);

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};