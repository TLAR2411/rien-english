<?php

namespace App\Enums;

/** Draft = only admin/teacher see it. Published = everyone sees it. */
enum PublishStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
