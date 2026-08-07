<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property string $test_name
 * @property string $result_value
 * @property string|null $unit
 * @property string|null $reference_range
 * @property Carbon $taken_at
 * @property int|null $created_by
 */
#[Fillable([
    'patient_id', 'test_name', 'result_value', 'unit', 'reference_range', 'taken_at', 'created_by',
])]
class LabResult extends Model
{
    /** @use HasFactory<\Database\Factories\LabResultFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'taken_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
