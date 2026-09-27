<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. "Gold", "A", "Diamond"
            $table->timestamps();

            $table->unique(['class_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arms');
    }
};
