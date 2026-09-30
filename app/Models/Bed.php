<?php

namespace App\Models;

use Database\Factories\BedFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $ward_id
 * @property int $bed_no
 */
#[Fillable(['ward_id', 'bed_no'])]
class Bed extends Model
{
    /** @use HasFactory<BedFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'bed_no' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Ward, $this>
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * The bed's current occupant, if any. A bed only ever points to an active
     * patient — it's freed automatically when that patient is discharged.
     *
     * @return HasOne<Patient, $this>
     */
    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    /**
     * Zero-padded display label, e.g. "01".
     */
    public function label(): string
    {
        return sprintf('%02d', $this->bed_no);
    }
}
