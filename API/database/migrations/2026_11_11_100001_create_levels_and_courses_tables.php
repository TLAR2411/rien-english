<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // A1, A2, B1 …
            $table->string('name');
            $table->string('name_km')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('title_km')->nullable();
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            // Paid courses come later (Phase 9); every course is free for now
            $table->boolean('is_free')->default(true);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('status', 20)->default('draft')->index(); // App\Enums\PublishStatus
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
        Schema::dropIfExists('levels');
    }
};
