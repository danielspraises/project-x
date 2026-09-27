<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_offering_id')->constrained()->cascadeOnDelete();

            // 'admin' now (ICT Admin / Department Officer / HOD assigns), 'student' once
            // the student portal exists in Phase 2 — this field is what lets that switch
            // happen later without any schema change, same pattern used elsewhere.
            $table->enum('registered_by', ['admin', 'student'])->default('admin');
            $table->foreignId('registered_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['student_id', 'course_offering_id'], 'course_registration_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_registrations');
    }
};
