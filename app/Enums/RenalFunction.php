<?php

namespace App\Enums;

enum RenalFunction: string
{
    case Normal = 'Normal';
    case MildImpairment = 'Mild_Impairment';
    case ModerateImpairment = 'Moderate_Impairment';
    case SevereImpairment = 'Severe_Impairment';
}
