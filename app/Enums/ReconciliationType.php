<?php

namespace App\Enums;

enum ReconciliationType: string
{
    case Admission = 'Admission';
    case Transfer = 'Transfer';
    case Discharge = 'Discharge';
}
