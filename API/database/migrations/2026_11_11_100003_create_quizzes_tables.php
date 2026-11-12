<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->unique()->constrained()->cascadeOnDelete(); // one quiz per lesson
            $table->string('title');
            $table->unsignedTinyInteger('pass_score')->default(70); // %
            $table->unsignedSmallInteger('time_limit_minutes')->nullable();
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // App\Enums\QuestionType
            $table->text('prompt');
            $table->text('explanation')->nullable();
            $table->text('explanation_km')->nullable();
            $table->unsignedSmallInteger('points')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('quizzes');
    }
};
