<?php

namespace App\Enums;

enum SourceType: string
{
    case PatientReport = 'Patient_Report';
    case Family = 'Family';
    case MedBottle = 'Med_Bottle';
    case PreviousRecord = 'Previous_Record';
    case Pharmacy = 'Pharmacy';
    case Other = 'Other';
}
