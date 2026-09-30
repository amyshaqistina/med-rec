<?php

use App\Enums\RiskLevel;
use App\Models\Patient;
use App\Models\User;
use App\Models\Ward;
use Livewire\Livewire;

test('technician can register a new patient', function () {
    $technician = User::factory()->create();

    $this->actingAs($technician);

    Livewire::test('pages::patients.create')
        ->set('first_name', 'Ahmad')
        ->set('last_name', 'Bin Ali')
        ->set('date_of_birth', now()->subYears(40)->format('Y-m-d'))
        ->set('admission_date', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    $patient = Patient::firstWhere('first_name', 'Ahmad');

    expect($patient)->not->toBeNull();
    expect($patient->mrn)->toStartWith('MRN-');
    expect($patient->created_by)->toBe($technician->id);
    expect($patient->risk_level)->toBe(RiskLevel::Low);
});

test('validation errors are shown for missing required fields', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::patients.create')
        ->set('first_name', '')
        ->call('save')
        ->assertHasErrors(['first_name' => 'required']);
});

test('physician cannot access the patient registration page', function () {
    $this->actingAs(User::factory()->physician()->create());

    $this->get(route('patients.create'))->assertForbidden();
});

test('selecting a ward offers only that ward\'s unoccupied beds', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create(['bed_capacity' => 2]);
    $beds = $ward->beds()->orderBy('bed_no')->get();
    Patient::factory()->create(['ward_id' => $ward->id, 'bed_id' => $beds[0]->id]);

    $component = Livewire::test('pages::patients.create')
        ->set('ward_id', (string) $ward->id);

    expect($component->viewData('availableBeds')->pluck('id')->all())->toBe([$beds[1]->id]);
});

test('registering a patient assigns the selected bed', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create(['bed_capacity' => 2]);
    $bed = $ward->beds()->first();

    Livewire::test('pages::patients.create')
        ->set('first_name', 'Ahmad')
        ->set('last_name', 'Bin Ali')
        ->set('date_of_birth', now()->subYears(40)->format('Y-m-d'))
        ->set('admission_date', now()->format('Y-m-d\TH:i'))
        ->set('ward_id', (string) $ward->id)
        ->set('bed_id', (string) $bed->id)
        ->call('save')
        ->assertHasNoErrors();

    $patient = Patient::firstWhere('first_name', 'Ahmad');

    expect($patient->bed_id)->toBe($bed->id);
});
