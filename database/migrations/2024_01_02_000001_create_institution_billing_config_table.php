<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_billing_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('billing_type', ['monthly', 'annual', 'per_student']);
            $table->decimal('rate_amount', 12, 2)->nullable(); // used for monthly/annual
            $table->decimal('per_student_rate', 12, 2)->nullable(); // used for per_student
            $table->string('currency', 10)->default('NGN');
            $table->date('billing_anniversary_date');
            $table->date('contract_start_date');
            $table->date('contract_end_date')->nullable();
            $table->enum('status', ['trial', 'active', 'suspended', 'cancelled'])->default('trial');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_billing_config');
    }
};
