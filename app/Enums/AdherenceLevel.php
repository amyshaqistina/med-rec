<?php

namespace App\Enums;

enum AdherenceLevel: string
{
    case Full = 'Full';
    case Partial = 'Partial';
    case None = 'None';
    case Unknown = 'Unknown';
}
