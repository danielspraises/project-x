<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_contents', function (Blueprint $table) {
            $table->longText('content')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('learning_contents', function (Blueprint $table) {
            $table->dropColumn('content');
        });
    }
};