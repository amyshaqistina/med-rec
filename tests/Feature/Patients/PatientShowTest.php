<?php

use App\Models\LabResult;
use App\Models\MedicationHistory;
use App\Models\Patient;
use App\Models\User;
use App\Models\Ward;
use Livewire\Livewire;

test('patient show page displays demographics and risk badge', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->highRisk()->create();

    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee($patient->full_name)
        ->assertSee($patient->mrn)
        ->assertSee('High risk', escape: false);
});

test('patient show page links back to the patient\'s ward', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create();
    $patient = Patient::factory()->inWard($ward)->create();

    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee('Back to '.$ward->name)
        ->assertSee(route('wards.show', $ward), escape: false);
});

test('patient show page has no back-to-ward link when the patient has no ward', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create(['ward_id' => null]);

    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertDontSee('Back to');
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

test('see all expands lab results in place instead of navigating away', function () {
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

    Livewire::test('pages::patients.show', ['patient' => $patient])
        ->assertSee('Potassium')
        ->assertDontSee('Sodium')
        ->set('showAllLabResults', true)
        ->assertSee('Potassium')
        ->assertSee('Sodium');
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

test('see all expands medication history in place instead of navigating away', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    MedicationHistory::factory()->count(5)->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->create([
        'patient_id' => $patient->id,
        'medication_name' => 'Oldest Medication',
        'created_at' => now()->subYear(),
    ]);

    Livewire::test('pages::patients.show', ['patient' => $patient])
        ->assertDontSee('Oldest Medication')
        ->set('showAllMedicationHistory', true)
        ->assertSee('Oldest Medication');
});

test('lab results widget can be searched by test name', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    LabResult::factory()->create(['patient_id' => $patient->id, 'test_name' => 'Sodium', 'taken_at' => now()]);
    LabResult::factory()->create(['patient_id' => $patient->id, 'test_name' => 'Potassium', 'taken_at' => now()]);

    Livewire::test('pages::patients.show', ['patient' => $patient])
        ->set('labResultsSearch', 'Sodium')
        ->assertSee('Sodium')
        ->assertDontSee('Potassium');
});

test('lab results widget can be sorted by test name', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    LabResult::factory()->create(['patient_id' => $patient->id, 'test_name' => 'Sodium', 'taken_at' => now()]);
    LabResult::factory()->create(['patient_id' => $patient->id, 'test_name' => 'Potassium', 'taken_at' => now()]);

    Livewire::test('pages::patients.show', ['patient' => $patient])
        ->call('sortLabResults', 'test_name')
        ->assertSeeInOrder(['Potassium', 'Sodium'])
        ->call('sortLabResults', 'test_name')
        ->assertSeeInOrder(['Sodium', 'Potassium']);
});

test('medication history widget can be searched by medication name', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Amlodipine']);
    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Metformin']);

    Livewire::test('pages::patients.show', ['patient' => $patient])
        ->set('medicationHistorySearch', 'Amlodipine')
        ->assertSee('Amlodipine')
        ->assertDontSee('Metformin');
});

test('medication history widget can be sorted by medication name', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Zolpidem']);
    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Amlodipine']);

    Livewire::test('pages::patients.show', ['patient' => $patient])
        ->call('sortMedicationHistory', 'medication_name')
        ->assertSeeInOrder(['Amlodipine', 'Zolpidem'])
        ->call('sortMedicationHistory', 'medication_name')
        ->assertSeeInOrder(['Zolpidem', 'Amlodipine']);
});

test('lab results widget sort direction can be toggled via the sort by dropdown', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    LabResult::factory()->create(['patient_id' => $patient->id, 'test_name' => 'Sodium', 'taken_at' => now()]);
    LabResult::factory()->create(['patient_id' => $patient->id, 'test_name' => 'Potassium', 'taken_at' => now()]);

    Livewire::test('pages::patients.show', ['patient' => $patient])
        ->set('labResultsSortBy', 'test_name')
        ->assertSet('labResultsSortDirection', 'desc')
        ->call('toggleLabResultsSortDirection')
        ->assertSet('labResultsSortDirection', 'asc')
        ->assertSeeInOrder(['Potassium', 'Sodium'])
        ->call('toggleLabResultsSortDirection')
        ->assertSet('labResultsSortDirection', 'desc')
        ->assertSeeInOrder(['Sodium', 'Potassium']);
});

test('medication history widget sort direction can be toggled via the sort by dropdown', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Zolpidem']);
    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Amlodipine']);

    Livewire::test('pages::patients.show', ['patient' => $patient])
        ->set('medicationHistorySortBy', 'medication_name')
        ->assertSet('medicationHistorySortDirection', 'desc')
        ->call('toggleMedicationHistorySortDirection')
        ->assertSet('medicationHistorySortDirection', 'asc')
        ->assertSeeInOrder(['Amlodipine', 'Zolpidem'])
        ->call('toggleMedicationHistorySortDirection')
        ->assertSet('medicationHistorySortDirection', 'desc')
        ->assertSeeInOrder(['Zolpidem', 'Amlodipine']);
});

test('lab results widget can be sorted by result value and reference range', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    LabResult::factory()->create(['patient_id' => $patient->id, 'test_name' => 'Sodium', 'result_value' => '9', 'reference_range' => '135-145', 'taken_at' => now()]);
    LabResult::factory()->create(['patient_id' => $patient->id, 'test_name' => 'Potassium', 'result_value' => '3', 'reference_range' => '120-130', 'taken_at' => now()]);

    Livewire::test('pages::patients.show', ['patient' => $patient])
        ->call('sortLabResults', 'result_value')
        ->assertSeeInOrder(['Potassium', 'Sodium'])
        ->call('sortLabResults', 'reference_range')
        ->assertSeeInOrder(['Potassium', 'Sodium']);
});

test('medication history widget can be sorted by dose amount and strength', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Zolpidem', 'dose_amount' => 200, 'strength' => '200mg']);
    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Amlodipine', 'dose_amount' => 100, 'strength' => '100mg']);

    Livewire::test('pages::patients.show', ['patient' => $patient])
        ->call('sortMedicationHistory', 'dose_amount')
        ->assertSeeInOrder(['Amlodipine', 'Zolpidem'])
        ->call('sortMedicationHistory', 'strength')
        ->assertSeeInOrder(['Amlodipine', 'Zolpidem']);
});
