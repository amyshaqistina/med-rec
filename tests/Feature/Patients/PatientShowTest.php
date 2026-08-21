<?php

use App\Models\LabResult;
use App\Models\MedicationHistory;
use App\Models\Patient;
use App\Models\User;

test('patient show page displays demographics and risk badge', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->highRisk()->create();

    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee($patient->full_name)
        ->assertSee($patient->mrn)
        ->assertSee('High risk', escape: false);
});

test('allergy banner shows documented allergies', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create(['allergies' => 'Penicillin, NSAIDs']);

    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee('Penicillin, NSAIDs');
});

test('allergy banner shows no known allergies when none documented', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create(['allergies' => null, 'known_adrs' => null]);

    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee('No known allergies documented');
});

test('patient show page has no lab results message when none recorded', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee('No lab results recorded yet.');
});

test('patient show page only shows the latest draw date lab results', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    LabResult::factory()->create([
        'patient_id' => $patient->id,
        'test_name' => 'Sodium',
        'taken_at' => now()->subDays(10),
    ]);

    LabResult::factory()->create([
        'patient_id' => $patient->id,
        'test_name' => 'Potassium',
        'taken_at' => now(),
    ]);

    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee('Potassium')
        ->assertDontSee('Sodium');
});

test('patient show page only shows the 5 most recent medication history entries', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    MedicationHistory::factory()->count(5)->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->create([
        'patient_id' => $patient->id,
        'medication_name' => 'Oldest Medication',
        'created_at' => now()->subYear(),
    ]);

    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertDontSee('Oldest Medication')
        ->assertSee('See all');
});
