<?php

namespace App\Enums;

enum DiscrepancySeverity: string
{
    case Critical = 'Critical';
    case Major = 'Major';
    case Minor = 'Minor';
    case Documentation = 'Documentation';

    /**
     * The Flux badge color used to render this severity.
     */
    public function color(): string
    {
        return match ($this) {
            self::Critical => 'red',
            self::Major => 'orange',
            self::Minor => 'yellow',
            self::Documentation => 'zinc',
        };
    }
}
