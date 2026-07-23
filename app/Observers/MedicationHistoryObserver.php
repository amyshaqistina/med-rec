<?php

namespace App\Observers;

use App\Models\MedicationHistory;
use App\Services\PatientRiskService;

class MedicationHistoryObserver
{
    public function __construct(
        private readonly PatientRiskService $riskService,
    ) {}

    /**
     * Handle the MedicationHistory "saved" event.
     */
    public function saved(MedicationHistory $medicationHistory): void
    {
        $this->recalculatePatientRisk($medicationHistory);
    }

    /**
     * Handle the MedicationHistory "deleted" event.
     */
    public function deleted(MedicationHistory $medicationHistory): void
    {
        $this->recalculatePatientRisk($medicationHistory);
    }

    private function recalculatePatientRisk(MedicationHistory $medicationHistory): void
    {
        if ($patient = $medicationHistory->patient) {
            $this->riskService->recalculate($patient);
        }
    }
}
