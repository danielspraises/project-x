<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g. 'courses', 'bulk_import', 'custom_roles'
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // for grouping in the UI
            // Core features are auto-enabled for every new institution and can still be
            // toggled off by Super Admin if needed — this flag is just a sensible default,
            // not a hard restriction (Super Admin retains full override, as established).
            $table->boolean('is_core')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
