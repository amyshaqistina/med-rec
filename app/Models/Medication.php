<?php

namespace App\Models;

use Database\Factories\MedicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 */
#[Fillable(['name'])]
class Medication extends Model
{
    /** @use HasFactory<MedicationFactory> */
    use HasFactory;

    /**
     * Common dosing frequency phrases offered as autocomplete suggestions
     * wherever a medication frequency is entered. Free text is still accepted.
     *
     * @var array<int, string>
     */
    public const FREQUENCY_OPTIONS = [
        'Once Daily', 'Twice Daily', 'Three Times Daily', 'Four Times Daily',
        'Every Morning', 'Every Night', 'Every Other Day', 'Every 4 Hours',
        'Every 6 Hours', 'Every 8 Hours', 'Weekly', 'Before Meals', 'After Meals',
        'At Bedtime', 'PRN', 'As Directed',
    ];
}
