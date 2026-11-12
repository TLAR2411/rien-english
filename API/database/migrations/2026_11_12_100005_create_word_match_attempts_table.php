<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Matching game of a vocabulary lesson (T35): one row per finished game. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('word_match_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedMediumInteger('user_id'); // users.id is a MEDIUMINT
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('score');   // pairs right
            $table->unsignedSmallInteger('total');   // pairs in the game
            $table->unsignedTinyInteger('percent');
            $table->timestamps();

            $table->index(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('word_match_attempts');
    }
};
