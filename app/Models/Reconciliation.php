<?php

namespace App\Models;

use App\Enums\ReconciliationStatus;
use App\Enums\ReconciliationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property ReconciliationType $type
 * @property ReconciliationStatus $status
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property int|null $technician_id
 * @property int|null $pharmacist_id
 * @property bool $bpmh_finalized
 * @property string|null $clinical_notes
 */
#[Fillable([
    'patient_id', 'type', 'status', 'started_at', 'completed_at',
    'technician_id', 'pharmacist_id', 'bpmh_finalized', 'clinical_notes',
])]
class Reconciliation extends Model
{
    /** @use HasFactory<\Database\Factories\ReconciliationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => ReconciliationType::class,
            'status' => ReconciliationStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'bpmh_finalized' => 'boolean',
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
     * Medication history rows whose provenance is this reconciliation event.
     * Not the scope used for discrepancy comparison — see DiscrepancyDetectionService.
     *
     * @return HasMany<MedicationHistory, $this>
     */
    public function medicationHistories(): HasMany
    {
        return $this->hasMany(MedicationHistory::class);
    }

    /**
     * @return HasMany<MedicationCurrent, $this>
     */
    public function medicationCurrents(): HasMany
    {
        return $this->hasMany(MedicationCurrent::class);
    }

    /**
     * @return HasMany<Discrepancy, $this>
     */
    public function discrepancies(): HasMany
    {
        return $this->hasMany(Discrepancy::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pharmacist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pharmacist_id');
    }
}
