<?php

namespace App\Observers;

use App\Enums\PatientStatus;
use App\Models\Bed;
use App\Models\Patient;
use App\Services\PatientRiskService;

class PatientObserver
{
    public function __construct(
        private readonly PatientRiskService $riskService,
    ) {}

    /**
     * Recalculate the patient's risk level whenever a risk-relevant field changes, free the
     * patient's bed as soon as they're discharged, and give any active ward patient without a
     * bed the first free one so every occupant shows up against a bed number.
     */
    public function saving(Patient $patient): void
    {
        if (! $patient->exists || $patient->isDirty([
            'date_of_birth', 'egfr', 'renal_function', 'hepatic_function', 'pregnancy_status', 'allergies',
        ])) {
            $patient->risk_level = $this->riskService->calculate($patient);
        }

        if ($patient->isDirty('status') && $patient->status === PatientStatus::Discharged) {
            $patient->bed_id = null;
        }

        if ($patient->bed_id === null && $patient->ward_id !== null && ($patient->status ?? PatientStatus::Active) === PatientStatus::Active) {
            $patient->bed_id = $this->firstFreeBedId($patient->ward_id);
        }
    }

    /**
     * The lowest-numbered unoccupied bed in the ward, or null when the ward is full.
     */
    private function firstFreeBedId(int $wardId): ?int
    {
        return Bed::query()
            ->where('ward_id', $wardId)
            ->whereDoesntHave('patient')
            ->orderBy('bed_no')
            ->value('id');
    }
}
