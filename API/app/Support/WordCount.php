<?php

namespace App\Support;

/**
 * Counts the words of a student's writing: everything between spaces / new lines.
 * The writing page counts the same way (WEB/src/utils/wordCount.js).
 */
class WordCount
{
    public static function of(?string $text): int
    {
        return count(preg_split('/\s+/u', trim((string) $text), -1, PREG_SPLIT_NO_EMPTY));
    }
}
