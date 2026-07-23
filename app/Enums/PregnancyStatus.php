<?php

namespace App\Enums;

enum PregnancyStatus: string
{
    case Unknown = 'Unknown';
    case NotPregnant = 'Not_Pregnant';
    case Pregnant = 'Pregnant';
}
