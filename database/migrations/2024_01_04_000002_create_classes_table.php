<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. "Primary 4", "JSS 2", "SS 3"
            $table->string('code');
            $table->unsignedTinyInteger('order'); // for sorting: Primary 1 = 1, Primary 2 = 2, etc.
            $table->timestamps();

            $table->unique(['institution_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
