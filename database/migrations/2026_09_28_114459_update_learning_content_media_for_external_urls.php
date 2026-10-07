<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_content_media', function (Blueprint $table) {
            $table->string('source_type', 20)
                ->default('upload')
                ->after('media_type');

            $table->string('external_url')
                ->nullable()
                ->after('path');

            $table->index(
                ['learning_content_id', 'source_type'],
                'lcm_content_source_idx'
            );
        });

        Schema::table('learning_content_media', function (Blueprint $table) {
            $table->string('path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('learning_content_media', function (Blueprint $table) {
            $table->dropIndex('lcm_content_source_idx');
            $table->dropColumn('external_url');
            $table->dropColumn('source_type');
        });

        Schema::table('learning_content_media', function (Blueprint $table) {
            $table->string('path')->nullable(false)->change();
        });
    }
};