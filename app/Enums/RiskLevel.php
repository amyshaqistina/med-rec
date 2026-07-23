<?php

namespace App\Enums;

enum RiskLevel: string
{
    case Low = 'Low';
    case Medium = 'Medium';
    case High = 'High';

    /**
     * The Flux badge color used to render this risk level.
     */
    public function color(): string
    {
        return match ($this) {
            self::Low => 'emerald',
            self::Medium => 'amber',
            self::High => 'red',
        };
    }
}
