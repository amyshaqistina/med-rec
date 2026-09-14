<?php

namespace App\Models;

use Database\Factories\LabResultFactory;
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
    /** @use HasFactory<LabResultFactory> */
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

    /**
     * Best-effort scanning aid only — reference_range is free text, not structured data,
     * so this is a heuristic, not a clinically validated flag. Always confirm the actual
     * reference range before acting on it.
     */
    public function flag(): ?string
    {
        if (! is_numeric($this->result_value) || blank($this->reference_range)) {
            return null;
        }

        $value = (float) $this->result_value;
        $range = trim($this->reference_range);

        if (preg_match('/^([\d.]+)\s*[-–]\s*([\d.]+)$/u', $range, $matches)) {
            $low = (float) $matches[1];
            $high = (float) $matches[2];
        } elseif (preg_match('/^[<≤]\s*([\d.]+)$/u', $range, $matches)) {
            $low = null;
            $high = (float) $matches[1];
        } elseif (preg_match('/^[>≥]\s*([\d.]+)$/u', $range, $matches)) {
            $low = (float) $matches[1];
            $high = null;
        } else {
            return null;
        }

        $span = ($low !== null && $high !== null)
            ? max($high - $low, 0.0001)
            : max((float) ($high ?? $low), 0.0001);
        $margin = $span * 0.1;

        if ($low !== null && $value < $low) {
            return $value < $low - $margin ? 'abnormal' : 'borderline';
        }

        if ($high !== null && $value > $high) {
            return $value > $high + $margin ? 'abnormal' : 'borderline';
        }

        return 'normal';
    }
}
