<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reconciliation_id', 'requested_by', 'status', 'input_hash', 'input_snapshot',
    'results', 'failure_reason', 'reviewed_at',
])]
class MedicationSafetyReview extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'input_snapshot' => 'array',
            'results' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Reconciliation, $this> */
    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
