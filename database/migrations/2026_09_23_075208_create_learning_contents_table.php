<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_contents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('institution_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            // Primary / Secondary
            $table->foreignId('subject_offering_id')
                ->nullable()
                ->constrained('subject_offerings')
                ->cascadeOnDelete();

            // Tertiary
            $table->foreignId('course_offering_id')
                ->nullable()
                ->constrained('course_offerings')
                ->cascadeOnDelete();

            $table->enum('content_type', [
                'lesson',
                'lecture',
            ]);

            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('status', [
                'draft',
                'published',
                'archived',
            ])->default('draft');

            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index([
                'institution_id',
                'content_type',
                'status',
            ]);

            $table->index([
                'subject_offering_id',
                'content_type',
            ]);

            $table->index([
                'course_offering_id',
                'content_type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_contents');
    }
};