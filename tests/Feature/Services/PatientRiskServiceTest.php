<?php

use App\Enums\Gender;
use App\Enums\HepaticFunction;
use App\Enums\PregnancyStatus;
use App\Enums\RenalFunction;
use App\Enums\RiskLevel;
use App\Models\MedicationHistory;
use App\Models\Patient;

test('patient with no risk factors is low risk', function () {
    $patient = Patient::factory()->lowRisk()->create();

    expect($patient->fresh()->risk_level)->toBe(RiskLevel::Low);
});

test('age over 65 is high risk', function () {
    $patient = Patient::factory()->lowRisk()->create([
        'date_of_birth' => now()->subYears(70),
    ]);

    expect($patient->fresh()->risk_level)->toBe(RiskLevel::High);
});

test('more than 5 active medications is high risk', function () {
    $patient = Patient::factory()->lowRisk()->create();

    MedicationHistory::factory()->count(6)->create(['patient_id' => $patient->id]);

    expect($patient->fresh()->risk_level)->toBe(RiskLevel::High);
});

test('egfr under 60 is high risk', function () {
    $patient = Patient::factory()->lowRisk()->create(['egfr' => 45]);

    expect($patient->fresh()->risk_level)->toBe(RiskLevel::High);
});

test('renal function fallback is used when egfr is absent', function () {
    $patient = Patient::factory()->lowRisk()->create([
        'egfr' => null,
        'renal_function' => RenalFunction::ModerateImpairment,
    ]);

    expect($patient->fresh()->risk_level)->toBe(RiskLevel::High);
});

test('hepatic impairment is high risk', function () {
    $patient = Patient::factory()->lowRisk()->create([
        'hepatic_function' => HepaticFunction::Moderate,
    ]);

    expect($patient->fresh()->risk_level)->toBe(RiskLevel::High);
});

test('pregnancy is high risk', function () {
    $patient = Patient::factory()->lowRisk()->create([
        'gender' => Gender::Female,
        'pregnancy_status' => PregnancyStatus::Pregnant,
    ]);

    expect($patient->fresh()->risk_level)->toBe(RiskLevel::High);
});

test('more than 3 allergies is medium risk', function () {
    $patient = Patient::factory()->lowRisk()->create([
        'allergies' => 'Penicillin, NSAIDs, Sulfa, Latex',
    ]);

    expect($patient->fresh()->risk_level)->toBe(RiskLevel::Medium);
});

test('non adherent medication is medium risk', function () {
    $patient = Patient::factory()->lowRisk()->create();

    MedicationHistory::factory()->nonAdherent()->create(['patient_id' => $patient->id]);

    expect($patient->fresh()->risk_level)->toBe(RiskLevel::Medium);
});

test('high risk factor wins over medium risk factors when combined', function () {
    $patient = Patient::factory()->create([
        'date_of_birth' => now()->subYears(70),
        'allergies' => 'Penicillin, NSAIDs, Sulfa, Latex',
    ]);

    expect($patient->fresh()->risk_level)->toBe(RiskLevel::High);
});
