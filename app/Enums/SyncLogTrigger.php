<?php

namespace App\Enums;

enum SyncLogTrigger: string
{
    case Created = 'created';
    case Manual = 'manual';
    case Scheduled = 'scheduled';
}