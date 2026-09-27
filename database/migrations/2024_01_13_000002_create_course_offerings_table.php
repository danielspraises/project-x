<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('programme_id')->constrained()->cascadeOnDelete();
            $table->string('level'); // which level takes it this offering — usually matches the course's default level
            $table->foreignId('lecturer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('requirement_type', ['compulsory', 'elective'])->default('compulsory');
            $table->timestamps();

            // The same course can't be offered twice for the same term/programme/level.
            $table->unique(['course_id', 'term_id', 'programme_id', 'level'], 'course_offering_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_offerings');
    }
};
