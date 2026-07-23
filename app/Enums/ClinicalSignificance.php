<?php

namespace App\Enums;

enum ClinicalSignificance: string
{
    case High = 'High';
    case Moderate = 'Moderate';
    case Low = 'Low';
    case Unknown = 'Unknown';
}
