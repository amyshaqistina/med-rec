<?php

namespace App\Enums;

enum PharmacistAssessment: string
{
    case Unintended = 'Unintended';
    case Intentional = 'Intentional';
    case RequiresClarification = 'Requires_Clarification';
}
