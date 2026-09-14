<?php

namespace App\Models;

use App\Enums\AdherenceLevel;
use App\Enums\MedicationRoute;
use App\Enums\SafetyCheckStatus;
use App\Enums\SourceType;
use App\Enums\TakingStatus;
use Database\Factories\MedicationCurrentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reconciliation_id
 * @property string $medication_name
 * @property string|null $dose
 * @property MedicationRoute|null $route
 * @property string|null $frequency
 * @property string|null $indication
 * @property string|null $ordered_by
 * @property Carbon|null $order_date
 * @property SafetyCheckStatus $safety_check_status
 * @property array|null $safety_label_data
 * @property array|null $safety_flags
 * @property Carbon|null $safety_checked_at
 */
#[Fillable([
    'reconciliation_id', 'medication_name', 'strength', 'dose_amount', 'dose_unit', 'dose', 'route', 'frequency',
    'timing', 'indication', 'is_patient_taking', 'adherence_level', 'non_adherence_reason', 'source_type',
    'ordered_by', 'order_date', 'safety_check_status', 'safety_label_data', 'safety_flags', 'safety_checked_at',
])]
class MedicationCurrent extends Model
{
    /** @use HasFactory<MedicationCurrentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'dose_amount' => 'decimal:2',
            'route' => MedicationRoute::class,
            'is_patient_taking' => TakingStatus::class,
            'adherence_level' => AdherenceLevel::class,
            'source_type' => SourceType::class,
            'order_date' => 'date',
            'safety_check_status' => SafetyCheckStatus::class,
            'safety_label_data' => 'array',
            'safety_flags' => 'array',
            'safety_checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Reconciliation, $this>
     */
    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class);
    }
}
