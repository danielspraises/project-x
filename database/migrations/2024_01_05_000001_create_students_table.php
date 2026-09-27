<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();

            // Nullable, for Phase 2 when students get their own portal login —
            // avoids reworking this table later, per the "no rework" principle.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('admission_number');
            $table->string('matric_number')->nullable(); // primarily used by tertiary institutions

            $table->string('first_name');
            $table->string('last_name');
            $table->string('other_names')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('passport_photo_path')->nullable();

            // Tertiary structure (nullable — only used when institution's education_level = tertiary)
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->string('level')->nullable(); // e.g. "100", "200"

            // Basic education structure (nullable — only used when education_level = primary/secondary)
            $table->foreignId('class_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('arm_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('status', ['active', 'graduated', 'withdrawn', 'suspended'])->default('active');

            $table->timestamps();

            $table->unique(['institution_id', 'admission_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
