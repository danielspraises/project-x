<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_content_media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('learning_content_id')
                ->constrained('learning_contents')
                ->cascadeOnDelete();

            $table->string('media_type', 20);
            $table->string('title')->nullable();
            $table->string('path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->timestamps();

            $table->index(
                ['learning_content_id', 'media_type'],
                'lcm_content_type_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_content_media');
    }
};