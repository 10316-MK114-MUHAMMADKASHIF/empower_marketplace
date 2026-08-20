<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pending = 'pending';
    case Generating = 'generating';
    case Completed = 'completed';
    case Failed = 'failed';
}
