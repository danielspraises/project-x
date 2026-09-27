<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Widen 'type' to a plain string and make it nullable — it no longer needs to be
        // a fixed enum, since valid options now depend on the institution's education_level.
        // (Raw SQL here avoids needing the doctrine/dbal package that Schema::change() requires.)
        DB::statement("ALTER TABLE institutions MODIFY type VARCHAR(50) NULL");

        Schema::table('institutions', function (Blueprint $table) {
            // Federal / State / Private — applies at every education level, not just tertiary.
            $table->enum('ownership', ['federal', 'state', 'private'])->nullable()->after('education_level');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn('ownership');
        });

        DB::statement("ALTER TABLE institutions MODIFY type VARCHAR(50) NOT NULL DEFAULT 'university'");
    }
};
