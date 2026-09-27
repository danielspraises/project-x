<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('class_teacher_assignments')) {
            return;
        }

        Schema::create('class_teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('arm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['institution_id', 'academic_session_id', 'term_id', 'class_id', 'arm_id'],
                'class_teacher_assignment_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_teacher_assignments');
    }
};
