<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What students do: quiz attempts, finished lessons, writing, and (later) paid enrollments.
 * users.id is a MEDIUMINT, so user columns use unsignedMediumInteger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $this->userColumn($table, 'user_id');
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('score')->default(0); // points earned
            $table->unsignedSmallInteger('total')->default(0); // points possible
            $table->unsignedTinyInteger('percent')->default(0);
            $table->boolean('passed')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'quiz_id']);
        });

        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->json('answer')->nullable(); // option id, typed text, or word list
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $this->userColumn($table, 'user_id');
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'lesson_id']);
        });

        Schema::create('writing_submissions', function (Blueprint $table) {
            $table->id();
            $this->userColumn($table, 'user_id');
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->longText('text');
            $table->string('status', 20)->default('pending')->index(); // App\Enums\WritingStatus
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('feedback')->nullable();
            $this->userColumn($table, 'reviewed_by', nullable: true);
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // For paid courses later (Phase 9) — not used yet
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $this->userColumn($table, 'user_id');
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('writing_submissions');
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('attempt_answers');
        Schema::dropIfExists('quiz_attempts');
    }

    private function userColumn(Blueprint $table, string $name, bool $nullable = false): void
    {
        $column = $table->unsignedMediumInteger($name);
        $foreign = $table->foreign($name)->references('id')->on('users');

        if ($nullable) {
            $column->nullable();
            $foreign->nullOnDelete();
        } else {
            $foreign->cascadeOnDelete();
        }
    }
};
