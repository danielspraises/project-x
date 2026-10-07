<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_components', function (Blueprint $table) {
            $table->id();

            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_scheme_id')->constrained()->cascadeOnDelete();

            // 'ca' | 'quiz' | 'assignment' | 'attendance' | 'exam'. Exactly one
            // 'exam' component per scheme; everything else counts toward ca_max.
            $table->string('type');
            $table->string('name'); // e.g. "CA 1", "Quiz", "Assignments"
            $table->unsignedInteger('max_score');
            $table->unsignedInteger('order')->default(0);

            // 'manual' today; 'assignment_sync' once Phase 2 links the
            // assignments module — a synced score is still overridable.
            $table->string('source')->default('manual');

            $table->timestamps();

            $table->index(['assessment_scheme_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_components');
    }
};
