<?php

namespace App\Enums;

enum ReliabilityRating: string
{
    case Definite = 'Definite';
    case Probable = 'Probable';
    case Possible = 'Possible';
}
