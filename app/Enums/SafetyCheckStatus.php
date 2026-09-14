<?php

namespace App\Enums;

enum SafetyCheckStatus: string
{
    case Pending = 'pending';
    case Complete = 'complete';
    case Unavailable = 'unavailable';
    case Failed = 'failed';
}
