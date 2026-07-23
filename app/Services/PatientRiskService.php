<?php

namespace App\Services;

use App\Enums\AdherenceLevel;
use App\Enums\HepaticFunction;
use App\Enums\PregnancyStatus;
use App\Enums\RenalFunction;
use App\Enums\RiskLevel;
use App\Enums\TakingStatus;
use App\Models\Patient;

/**
 * Implements the SRS FR-PM-002.1 risk stratification rules.
 *
 * Any single high-risk factor present results in a High rating; otherwise any
 * medium-risk factor results in Medium; otherwise the patient is Low risk.
 *
 * Not implemented (no supporting data in this schema): "recent hospitalization
 * <30 days" and "complex comorbidities >3" factors from the SRS.
 */
class PatientRiskService
{
    public function calculate(Patient $patient): RiskLevel
    {
        if ($this->hasHighRiskFactor($patient)) {
            return RiskLevel::High;
        }

        if ($this->hasMediumRiskFactor($patient)) {
            return RiskLevel::Medium;
        }

        return RiskLevel::Low;
    }

    /**
     * Recalculate and persist the patient's risk level without re-triggering
     * the saving observer.
     */
    public function recalculate(Patient $patient): RiskLevel
    {
        $level = $this->calculate($patient);

        // risk_level is intentionally excluded from Patient's #[Fillable] list
        // (system-computed, not user-settable), so it must be set directly
        // rather than via mass assignment.
        $patient->risk_level = $level;
        $patient->saveQuietly();

        return $level;
    }

    private function hasHighRiskFactor(Patient $patient): bool
    {
        if ($patient->date_of_birth?->age > 65) {
            return true;
        }

        if ($this->activeMedicationCount($patient) > 5) {
            return true;
        }

        if ($patient->egfr !== null) {
            if ((float) $patient->egfr < 60) {
                return true;
            }
        } elseif ($patient->renal_function !== RenalFunction::Normal) {
            return true;
        }

        if ($patient->hepatic_function !== HepaticFunction::Normal) {
            return true;
        }

        if ($patient->pregnancy_status === PregnancyStatus::Pregnant) {
            return true;
        }

        return false;
    }

    private function hasMediumRiskFactor(Patient $patient): bool
    {
        if ($this->allergyCount($patient) > 3) {
            return true;
        }

        if ($this->hasNonAdherentMedication($patient)) {
            return true;
        }

        return false;
    }

    private function activeMedicationCount(Patient $patient): int
    {
        if (! $patient->exists) {
            return 0;
        }

        return $patient->medicationHistories()
            ->where('is_patient_taking', TakingStatus::Yes)
            ->count();
    }

    private function hasNonAdherentMedication(Patient $patient): bool
    {
        if (! $patient->exists) {
            return false;
        }

        return $patient->medicationHistories()
            ->whereIn('adherence_level', [AdherenceLevel::Partial, AdherenceLevel::None])
            ->exists();
    }

    /**
     * Heuristic: allergies is freetext, so approximate a count via comma-separated entries.
     */
    private function allergyCount(Patient $patient): int
    {
        $allergies = trim((string) $patient->allergies);

        if ($allergies === '') {
            return 0;
        }

        return substr_count($allergies, ',') + 1;
    }
}
