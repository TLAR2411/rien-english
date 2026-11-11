<?php

namespace App\Enums;

enum WritingStatus: string
{
    case Pending = 'pending';
    case Reviewed = 'reviewed';
}
