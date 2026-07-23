<?php

namespace App\Models;

use App\Enums\AdherenceLevel;
use App\Enums\MedicationRoute;
use App\Enums\ReliabilityRating;
use App\Enums\SourceType;
use App\Enums\TakingStatus;
use App\Observers\MedicationHistoryObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property int|null $reconciliation_id
 * @property string $medication_name
 * @property string|null $strength
 * @property float|null $dose_amount
 * @property string|null $dose_unit
 * @property MedicationRoute|null $route
 * @property string|null $frequency
 * @property string|null $timing
 * @property string|null $indication
 * @property Carbon|null $start_date
 * @property string|null $prescriber_name
 * @property TakingStatus $is_patient_taking
 * @property AdherenceLevel|null $adherence_level
 * @property string|null $non_adherence_reason
 * @property SourceType|null $source_type
 * @property ReliabilityRating|null $reliability_rating
 * @property string|null $notes
 * @property int|null $created_by
 */
#[ObservedBy(MedicationHistoryObserver::class)]
#[Fillable([
    'patient_id', 'reconciliation_id', 'medication_name', 'strength', 'dose_amount', 'dose_unit',
    'route', 'frequency', 'timing', 'indication', 'start_date', 'prescriber_name',
    'is_patient_taking', 'adherence_level', 'non_adherence_reason', 'source_type',
    'reliability_rating', 'notes', 'created_by',
])]
class MedicationHistory extends Model
{
    /** @use HasFactory<\Database\Factories\MedicationHistoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'dose_amount' => 'decimal:2',
            'route' => MedicationRoute::class,
            'start_date' => 'date',
            'is_patient_taking' => TakingStatus::class,
            'adherence_level' => AdherenceLevel::class,
            'source_type' => SourceType::class,
            'reliability_rating' => ReliabilityRating::class,
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
     * @return BelongsTo<Reconciliation, $this>
     */
    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
