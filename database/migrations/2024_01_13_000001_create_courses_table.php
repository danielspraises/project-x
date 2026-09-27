<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('code'); // e.g. "CSC201"
            $table->string('title');
            $table->unsignedTinyInteger('credit_unit');
            $table->string('level'); // e.g. "200" — which level this course is normally taken at
            $table->timestamps();

            $table->unique(['institution_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
