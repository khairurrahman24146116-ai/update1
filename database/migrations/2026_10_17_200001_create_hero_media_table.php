<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_media', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->string('file_type')->comment('image or video');
            $table->string('mime_type');
            $table->unsignedInteger('file_size')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('poster_path')->nullable()->comment('poster image for video');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_media');
    }
};
