<?php

namespace App\Enums;

enum MedicationRoute: string
{
    case PO = 'PO';
    case IV = 'IV';
    case IM = 'IM';
    case SC = 'SC';
    case Topical = 'Topical';
    case Inhaled = 'Inhaled';
    case Other = 'Other';
}
