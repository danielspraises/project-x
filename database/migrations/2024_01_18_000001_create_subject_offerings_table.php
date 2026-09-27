<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subject_offerings')) {
            return;
        }

        Schema::create('subject_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('arm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['institution_id', 'academic_session_id', 'term_id', 'class_id', 'arm_id', 'subject_id'],
                'subject_offering_unique'
            );
            $table->index(
                ['institution_id', 'academic_session_id', 'term_id'],
                'subj_offering_context_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_offerings');
    }
};
