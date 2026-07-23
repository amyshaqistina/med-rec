<?php

namespace App\Enums;

enum UserRole: string
{
    case Technician = 'technician';
    case Pharmacist = 'pharmacist';
    case Physician = 'physician';
    case Nurse = 'nurse';
    case Manager = 'manager';
    case Admin = 'admin';
    case Superuser = 'superuser';

    public function label(): string
    {
        return match ($this) {
            self::Technician => 'Pharmacy Technician',
            self::Pharmacist => 'Clinical Pharmacist',
            self::Physician => 'Physician',
            self::Nurse => 'Nurse',
            self::Manager => 'Pharmacy Manager',
            self::Admin => 'System Administrator',
            self::Superuser => 'Superuser',
        };
    }
}
