<?php

namespace App\Models;

use App\Enums\MedicationRoute;
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
 */
#[Fillable([
    'reconciliation_id', 'medication_name', 'dose', 'route', 'frequency', 'indication', 'ordered_by', 'order_date',
])]
class MedicationCurrent extends Model
{
    /** @use HasFactory<\Database\Factories\MedicationCurrentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'route' => MedicationRoute::class,
            'order_date' => 'date',
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
