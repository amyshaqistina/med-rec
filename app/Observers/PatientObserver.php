<?php

namespace App\Observers;

use App\Models\Patient;
use App\Services\PatientRiskService;

class PatientObserver
{
    public function __construct(
        private readonly PatientRiskService $riskService,
    ) {}

    /**
     * Recalculate the patient's risk level whenever a risk-relevant field changes.
     */
    public function saving(Patient $patient): void
    {
        if (! $patient->exists || $patient->isDirty([
            'date_of_birth', 'egfr', 'renal_function', 'hepatic_function', 'pregnancy_status', 'allergies',
        ])) {
            $patient->risk_level = $this->riskService->calculate($patient);
        }
    }
}
