<?php

use App\Enums\TakingStatus;
use App\Models\MedicationHistory;
use App\Models\Patient;
use App\Models\User;
use Livewire\Livewire;

test('add medication history page starts with a single blank form', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    Livewire::test('pages::patients.medication-history-create', ['patient' => $patient])
        ->assertSet('rows', fn ($rows) => count($rows) === 1);
});

test('the add medication button adds another blank form', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    Livewire::test('pages::patients.medication-history-create', ['patient' => $patient])
        ->call('addRow')
        ->assertSet('rows', fn ($rows) => count($rows) === 2);
});

test('a medication history entry can be added', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    Livewire::test('pages::patients.medication-history-create', ['patient' => $patient])
        ->set('rows.0.medication_name', 'Lisinopril')
        ->set('rows.0.frequency', 'Once Daily')
        ->set('rows.0.is_patient_taking', TakingStatus::Yes->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('patients.show', $patient));

    expect(MedicationHistory::where('patient_id', $patient->id)->where('medication_name', 'Lisinopril')->exists())->toBeTrue();
});

test('medication history cannot be added for a discharged patient', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->discharged()->create();

    Livewire::test('pages::patients.medication-history-create', ['patient' => $patient])
        ->set('rows.0.medication_name', 'Lisinopril')
        ->call('save')
        ->assertForbidden();
});
