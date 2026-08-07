<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\HepaticFunction;
use App\Enums\PatientStatus;
use App\Enums\PregnancyStatus;
use App\Enums\RenalFunction;
use App\Enums\RiskLevel;
use App\Observers\PatientObserver;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $mrn
 * @property string $first_name
 * @property string $last_name
 * @property Carbon $date_of_birth
 * @property Gender|null $gender
 * @property string|null $contact_primary
 * @property string|null $contact_secondary
 * @property string|null $email
 * @property string|null $address_street
 * @property string|null $address_city
 * @property string|null $address_postcode
 * @property string|null $address_state
 * @property Carbon $admission_date
 * @property Carbon|null $discharge_date
 * @property int|null $ward_id
 * @property string|null $bed_no
 * @property string|null $primary_diagnosis
 * @property string|null $allergies
 * @property string|null $known_adrs
 * @property RenalFunction $renal_function
 * @property float|null $egfr
 * @property HepaticFunction $hepatic_function
 * @property PregnancyStatus $pregnancy_status
 * @property string|null $notes
 * @property PatientStatus $status
 * @property RiskLevel $risk_level
 * @property int|null $created_by
 * @property int|null $updated_by
 */
#[ObservedBy(PatientObserver::class)]
#[Fillable([
    'first_name', 'last_name', 'date_of_birth', 'gender',
    'contact_primary', 'contact_secondary', 'email',
    'address_street', 'address_city', 'address_postcode', 'address_state',
    'admission_date', 'discharge_date', 'ward_id', 'bed_no', 'primary_diagnosis',
    'allergies', 'known_adrs', 'renal_function', 'egfr', 'hepatic_function',
    'pregnancy_status', 'notes', 'status', 'created_by', 'updated_by',
])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'admission_date' => 'datetime',
            'discharge_date' => 'datetime',
            'egfr' => 'decimal:2',
            'gender' => Gender::class,
            'renal_function' => RenalFunction::class,
            'hepatic_function' => HepaticFunction::class,
            'pregnancy_status' => PregnancyStatus::class,
            'status' => PatientStatus::class,
            'risk_level' => RiskLevel::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Patient $patient): void {
            if (blank($patient->mrn)) {
                $patient->mrn = static::generateMrn();
            }
        });
    }

    /**
     * Generate a unique, human-readable medical record number, e.g. "MRN-26-000001".
     */
    public static function generateMrn(): string
    {
        $year = now()->format('y');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $sequence = DB::table('patients')
                ->whereYear('created_at', now()->year)
                ->count() + 1 + $attempt;

            $candidate = sprintf('MRN-%s-%06d', $year, $sequence);

            if (! static::where('mrn', $candidate)->exists()) {
                return $candidate;
            }
        }

        return sprintf('MRN-%s-%06d', $year, random_int(100000, 999999));
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim("{$this->first_name} {$this->last_name}"),
        );
    }

    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->date_of_birth->age,
        );
    }

    /**
     * @return BelongsTo<Ward, $this>
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * @return HasMany<MedicationHistory, $this>
     */
    public function medicationHistories(): HasMany
    {
        return $this->hasMany(MedicationHistory::class);
    }

    /**
     * @return HasMany<Reconciliation, $this>
     */
    public function reconciliations(): HasMany
    {
        return $this->hasMany(Reconciliation::class);
    }

    /**
     * @return HasMany<LabResult, $this>
     */
    public function labResults(): HasMany
    {
        return $this->hasMany(LabResult::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
