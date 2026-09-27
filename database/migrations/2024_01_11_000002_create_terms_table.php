<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. "First Semester" (tertiary) or "First Term" (basic education)
            $table->unsignedTinyInteger('order'); // 1, 2, 3 — for sorting within a session
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['upcoming', 'active', 'closed'])->default('upcoming');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
