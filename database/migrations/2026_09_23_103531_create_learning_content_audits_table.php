<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('learning_content_audits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('institution_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('learning_content_id')
                ->constrained('learning_contents')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('action', 50);

            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();

            $table->text('remarks')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(
                ['institution_id', 'learning_content_id'],
                'lca_institution_content_idx'
            );

            $table->index(
                ['institution_id', 'action'],
                'lca_institution_action_idx'
            );

            $table->index(
                ['learning_content_id', 'created_at'],
                'lca_content_created_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_content_audits');
    }
};