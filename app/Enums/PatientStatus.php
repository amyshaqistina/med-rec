<?php

namespace App\Enums;

enum PatientStatus: string
{
    case Active = 'Active';
    case Discharged = 'Discharged';
    case Archived = 'Archived';
}
