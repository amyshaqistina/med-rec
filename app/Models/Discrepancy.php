<?php

namespace App\Models;

use App\Enums\ClinicalSignificance;
use App\Enums\DiscrepancySeverity;
use App\Enums\DiscrepancyStatus;
use App\Enums\DiscrepancyType;
use App\Enums\PharmacistAssessment;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reconciliation_id
 * @property int|null $medication_history_id
 * @property int|null $medication_current_id
 * @property DiscrepancyType $type
 * @property DiscrepancySeverity $severity
 * @property ClinicalSignificance $clinical_significance
 * @property DiscrepancyStatus $status
 * @property PharmacistAssessment|null $pharmacist_assessment
 * @property string|null $description
 * @property string|null $clinical_note
 * @property int|null $resolved_by
 * @property Carbon|null $resolved_at
 */
#[Fillable([
    'reconciliation_id', 'medication_history_id', 'medication_current_id', 'type', 'severity',
    'clinical_significance', 'status', 'pharmacist_assessment', 'description', 'clinical_note',
    'resolved_by', 'resolved_at',
])]
class Discrepancy extends Model
{
    /** @use HasFactory<\Database\Factories\DiscrepancyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => DiscrepancyType::class,
            'severity' => DiscrepancySeverity::class,
            'clinical_significance' => ClinicalSignificance::class,
            'status' => DiscrepancyStatus::class,
            'pharmacist_assessment' => PharmacistAssessment::class,
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Reconciliation, $this>
     */
    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class);
    }

    /**
     * @return BelongsTo<MedicationHistory, $this>
     */
    public function medicationHistory(): BelongsTo
    {
        return $this->belongsTo(MedicationHistory::class);
    }

    /**
     * @return BelongsTo<MedicationCurrent, $this>
     */
    public function medicationCurrent(): BelongsTo
    {
        return $this->belongsTo(MedicationCurrent::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
