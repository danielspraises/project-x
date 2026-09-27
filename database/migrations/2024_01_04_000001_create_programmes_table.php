<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programmes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedTinyInteger('duration_years')->default(4);
            $table->timestamps();

            $table->unique(['institution_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programmes');
    }
};
