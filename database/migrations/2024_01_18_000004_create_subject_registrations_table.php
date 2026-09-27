<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subject_registrations')) {
            return;
        }

        Schema::create('subject_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_offering_id')->constrained()->cascadeOnDelete();
            $table->enum('registered_by', ['admin', 'student'])->default('admin');
            $table->foreignId('registered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'subject_offering_id'], 'subject_registration_unique');
            $table->index(['institution_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_registrations');
    }
};
