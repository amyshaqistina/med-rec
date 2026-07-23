<?php

namespace App\Enums;

enum DiscrepancyType: string
{
    case Omission = 'Omission';
    case Commission = 'Commission';
    case DoseChange = 'Dose_Change';
    case FrequencyChange = 'Frequency_Change';
    case RouteChange = 'Route_Change';
    case Duplication = 'Duplication';
    case TherapeuticDuplication = 'Therapeutic_Duplication';
    case Other = 'Other';

    /**
     * A short, human-readable label for the discrepancy list.
     */
    public function label(): string
    {
        return match ($this) {
            self::Omission => 'Omission',
            self::Commission => 'New medication',
            self::DoseChange => 'Dose changed',
            self::FrequencyChange => 'Frequency changed',
            self::RouteChange => 'Route changed',
            self::Duplication => 'Duplicate medication',
            self::TherapeuticDuplication => 'Therapeutic duplication',
            self::Other => 'Other',
        };
    }
}
