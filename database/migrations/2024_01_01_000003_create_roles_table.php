<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            // nullable institution_id: Super Admin role is global (platform-level, not tied to a school)
            $table->foreignId('institution_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name'); // super_admin, ict_admin, faculty_officer, department_officer, hod, lecturer
            $table->string('slug');
            $table->timestamps();

            $table->unique(['institution_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
