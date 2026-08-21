<?php

use App\Models\MedicationHistory;
use App\Models\Patient;
use App\Models\User;

test('medication history list page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    $this->get(route('patients.medication-history.index', $patient))->assertOk();
});

test('medication history list page shows all recorded entries', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    $first = MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Amlodipine']);
    $second = MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Metformin']);

    $this->get(route('patients.medication-history.index', $patient))
        ->assertOk()
        ->assertSee($first->medication_name)
        ->assertSee($second->medication_name);
});
