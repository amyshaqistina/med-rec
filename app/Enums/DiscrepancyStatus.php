<?php

namespace App\Enums;

enum DiscrepancyStatus: string
{
    case Identified = 'Identified';
    case UnderReview = 'Under_Review';
    case Resolved = 'Resolved';
    case PendingPrescriber = 'Pending_Prescriber';
    case Closed = 'Closed';
}
