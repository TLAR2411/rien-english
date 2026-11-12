<?php

namespace App\Enums;

/** The 5 skills a lesson can teach. Add a case here to add a skill (no migration needed). */
enum LessonSkill: string
{
    case Grammar = 'grammar';
    case Reading = 'reading';
    case Listening = 'listening';
    case Writing = 'writing';
    case Vocabulary = 'vocabulary';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
