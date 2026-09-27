<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_results', function (Blueprint $table) {
            $table->foreignId('result_submission_id')
                ->nullable()
                ->after('course_offering_id')
                ->constrained('result_submissions')
                ->nullOnDelete();

            $table->index(
                ['institution_id', 'result_submission_id'],
                'sr_inst_submission_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('student_results', function (Blueprint $table) {
            $table->dropIndex('sr_inst_submission_idx');
            $table->dropForeign(['result_submission_id']);
            $table->dropColumn('result_submission_id');
        });
    }
};
