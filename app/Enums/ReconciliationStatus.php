<?php

namespace App\Enums;

enum ReconciliationStatus: string
{
    case Draft = 'Draft';
    case InProgress = 'In_Progress';
    case Completed = 'Completed';
    case Closed = 'Closed';

    /**
     * The Flux badge color used to render this status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::InProgress => 'amber',
            self::Completed => 'emerald',
            self::Closed => 'teal',
        };
    }
}
