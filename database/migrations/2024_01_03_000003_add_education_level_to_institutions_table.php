<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            // tertiary = university/polytechnic/college of education (uses Faculty > Department > Programme)
            // secondary = JSS/SSS (uses Class > Arm, terms instead of semesters)
            // primary = elementary school (uses Class > Arm, terms instead of semesters)
            $table->enum('education_level', ['tertiary', 'secondary', 'primary'])
                ->default('tertiary')
                ->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn('education_level');
        });
    }
};
