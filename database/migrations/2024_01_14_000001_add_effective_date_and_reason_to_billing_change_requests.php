<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_change_requests', function (Blueprint $table) {
            $table->date('effective_date')->nullable()->after('requested_value');
            $table->text('reason')->nullable()->after('effective_date');
        });
    }

    public function down(): void
    {
        Schema::table('billing_change_requests', function (Blueprint $table) {
            $table->dropColumn(['effective_date', 'reason']);
        });
    }
};
