<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_card_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('education_level', ['primary', 'secondary']);
            $table->unsignedInteger('version')->default(1);
            $table->text('description')->nullable();
            $table->string('view');
            $table->json('configuration')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['education_level', 'is_active'], 'rct_education_active_idx');
        });

        Schema::create('institution_report_card_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id');
            $table->foreign('institution_id', 'inst_rct_institution_fk')
                ->references('id')->on('institutions')->cascadeOnDelete();

            $table->foreignId('report_card_template_id');
            $table->foreign('report_card_template_id', 'inst_rct_template_fk')
                ->references('id')->on('report_card_templates')->cascadeOnDelete();

            $table->foreignId('assigned_by')->nullable();
            $table->foreign('assigned_by', 'inst_rct_assigned_by_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique('institution_id', 'inst_rct_institution_unique');
        });

        Schema::create('transcript_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('education_level', ['tertiary']);
            $table->unsignedInteger('version')->default(1);
            $table->text('description')->nullable();
            $table->string('view');
            $table->json('configuration')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['education_level', 'is_active'], 'tt_education_active_idx');
        });

        Schema::create('institution_transcript_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id');
            $table->foreign('institution_id', 'inst_tt_institution_fk')
                ->references('id')->on('institutions')->cascadeOnDelete();

            $table->foreignId('transcript_template_id');
            $table->foreign('transcript_template_id', 'inst_tt_template_fk')
                ->references('id')->on('transcript_templates')->cascadeOnDelete();

            $table->foreignId('assigned_by')->nullable();
            $table->foreign('assigned_by', 'inst_tt_assigned_by_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique('institution_id', 'inst_tt_institution_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_transcript_templates');
        Schema::dropIfExists('transcript_templates');
        Schema::dropIfExists('institution_report_card_templates');
        Schema::dropIfExists('report_card_templates');
    }
};
