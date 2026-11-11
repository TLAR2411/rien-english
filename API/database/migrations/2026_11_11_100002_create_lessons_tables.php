<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->string('skill', 20)->index(); // App\Enums\LessonSkill
            $table->string('title_en');
            $table->string('title_km')->nullable();
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();    // HTML
            $table->longText('content_km')->nullable(); // HTML
            $table->string('audio_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft')->index(); // App\Enums\PublishStatus
            $table->timestamps();
        });

        Schema::create('lesson_examples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('sentence');
            $table->string('translation_km')->nullable();
            $table->string('audio_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_examples');
        Schema::dropIfExists('lessons');
    }
};
