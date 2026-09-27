<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->enum('type', ['university', 'polytechnic', 'college_of_education', 'private'])->default('university');
            $table->enum('status', ['trial', 'active', 'suspended', 'cancelled'])->default('trial');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
