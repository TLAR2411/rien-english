<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The task of a writing lesson (the task text itself is the lesson content):
 * a checklist for the student, a word target, and a sample answer that
 * students only see after they send their own writing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->json('checklist')->nullable()->after('transcript');           // ["Use the Present Simple", …]
            $table->unsignedSmallInteger('min_words')->nullable()->after('checklist');
            $table->unsignedSmallInteger('max_words')->nullable()->after('min_words');
            $table->longText('sample_answer')->nullable()->after('max_words');    // HTML
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['checklist', 'min_words', 'max_words', 'sample_answer']);
        });
    }
};
