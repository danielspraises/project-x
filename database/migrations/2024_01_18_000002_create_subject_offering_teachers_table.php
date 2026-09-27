<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subject_offering_teachers')) {
            return;
        }

        Schema::create('subject_offering_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['lead', 'supporting'])->default('supporting');
            $table->timestamps();

            $table->unique(['subject_offering_id', 'teacher_id'], 'subject_offering_teacher_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_offering_teachers');
    }
};
