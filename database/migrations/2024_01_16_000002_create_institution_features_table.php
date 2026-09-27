<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();

            $table->unique(['institution_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_features');
    }
};
