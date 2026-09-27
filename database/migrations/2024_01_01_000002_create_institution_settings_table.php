<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('key'); // e.g. grading_scale, cgpa_formula, carryover_policy, session_structure
            $table->json('value');
            $table->timestamps();

            $table->unique(['institution_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_settings');
    }
};
