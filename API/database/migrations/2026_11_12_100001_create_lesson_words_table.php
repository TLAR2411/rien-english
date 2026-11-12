<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * New words of a lesson ("Words to know" under a reading text).
 * Vocabulary lessons (T35) use the same list for their flashcards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('word', 100);
            $table->string('meaning_en')->nullable(); // simple English meaning
            $table->string('meaning_km')->nullable();
            $table->string('example')->nullable();    // a sentence with the word
            $table->string('audio_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_words');
    }
};
