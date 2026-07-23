<?php

use App\Enums\TakingStatus;
use App\Models\MedicationHistory;
use App\Models\Patient;
use App\Models\Reconciliation;
use App\Models\User;
use Livewire\Livewire;

test('existing medication history rows are loaded', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Amlodipine']);
    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Metformin']);

    $this->get(route('patients.medication-history', $patient))
        ->assertOk()
        ->assertSee('Amlodipine')
        ->assertSee('Metformin');
});

test('a new medication row can be added and saved', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    Livewire::test('pages::patients.medication-history', ['patient' => $patient])
        ->call('addRow')
        ->set('rows.0.medication_name', 'Lisinopril')
        ->set('rows.0.frequency', 'Once Daily')
        ->set('rows.0.is_patient_taking', TakingStatus::Yes->value)
        ->call('save')
        ->assertHasNoErrors();

    expect(MedicationHistory::where('patient_id', $patient->id)->where('medication_name', 'Lisinopril')->exists())->toBeTrue();
});

test('removing a row deletes the medication history record', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $medication = MedicationHistory::factory()->create(['patient_id' => $patient->id]);

    Livewire::test('pages::patients.medication-history', ['patient' => $patient])
        ->call('removeRow', 0);

    expect(MedicationHistory::find($medication->id))->toBeNull();
});

test('allergy banner is shown on the medication history page', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create(['allergies' => 'Penicillin']);

    $this->get(route('patients.medication-history', $patient))
        ->assertOk()
        ->assertSee('Penicillin');
});

test('marking bpmh compiled finalizes the linked reconciliation', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);

    Livewire::test('pages::patients.medication-history', ['patient' => $patient])
        ->set('reconciliation', $reconciliation->id)
        ->call('markCompiled');

    expect($reconciliation->fresh()->bpmh_finalized)->toBeTrue();
});

test('medication history cannot be saved for a discharged patient', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->discharged()->create();

    Livewire::test('pages::patients.medication-history', ['patient' => $patient])
        ->call('addRow')
        ->set('rows.0.medication_name', 'Lisinopril')
        ->call('save')
        ->assertForbidden();
});
