<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('learning_contents', 'content_kind')) {
            Schema::table('learning_contents', function (Blueprint $table) {
                $table->string('content_kind', 40)
                    ->default('standard')
                    ->after('content_type');

                $table->index(
                    ['institution_id', 'content_type', 'content_kind'],
                    'lc_type_kind_idx'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('learning_contents', 'content_kind')) {
            Schema::table('learning_contents', function (Blueprint $table) {
                $table->dropIndex('lc_type_kind_idx');
                $table->dropColumn('content_kind');
            });
        }
    }
};
