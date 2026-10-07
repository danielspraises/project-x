<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_schemes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();

            // Exactly one of these is set — tertiary vs basic-ed, same split
            // pattern used on student_results and result_submissions.
            $table->foreignId('course_offering_id')->nullable()->constrained('course_offerings')->cascadeOnDelete();
            $table->foreignId('subject_offering_id')->nullable()->constrained('subject_offerings')->cascadeOnDelete();

            // The institution-fixed split this scheme was built from
            // (Institution::assessmentSettings()) — components must sum to these.
            $table->unsignedInteger('ca_max');
            $table->unsignedInteger('exam_max');

            $table->string('status')->default('draft'); // draft -> locked
            $table->timestamp('locked_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // MySQL unique indexes treat NULL as distinct, so this allows
            // unlimited NULL course_offering_id rows (basic-ed schemes) while
            // still capping tertiary schemes at one per course offering.
            $table->unique('course_offering_id');
            $table->unique('subject_offering_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_schemes');
    }
};
