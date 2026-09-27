<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_offerings', function (Blueprint $table) {
            // Default 'compulsory' so every existing offering keeps behaving
            // exactly as it does today (eligible = whole class/arm) — only
            // offerings explicitly marked 'elective' going forward will need
            // the manual student picker instead of auto-enrollment.
            $table->string('requirement_type')->default('compulsory')->after('scope');
        });
    }

    public function down(): void
    {
        Schema::table('subject_offerings', function (Blueprint $table) {
            $table->dropColumn('requirement_type');
        });
    }
};
