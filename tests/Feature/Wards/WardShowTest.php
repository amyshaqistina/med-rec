<?php

use App\Models\Patient;
use App\Models\Reconciliation;
use App\Models\User;
use App\Models\Ward;
use Livewire\Livewire;

test('ward patient list shows only patients assigned to that ward', function () {
    $this->actingAs(User::factory()->create());

    $wardA = Ward::factory()->create(['name' => 'Ward 1']);
    $wardB = Ward::factory()->create(['name' => 'Ward 2']);

    $inWard = Patient::factory()->create(['ward_id' => $wardA->id, 'first_name' => 'Ahmad']);
    $elsewhere = Patient::factory()->create(['ward_id' => $wardB->id, 'first_name' => 'Siti']);

    $this->get(route('wards.show', $wardA))
        ->assertOk()
        ->assertSee('Ahmad')
        ->assertDontSee('Siti');
});

test('patients in a ward can be searched by name or mrn', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create();
    $match = Patient::factory()->create(['ward_id' => $ward->id, 'first_name' => 'Ahmad', 'last_name' => 'Bin Ali']);
    $other = Patient::factory()->create(['ward_id' => $ward->id, 'first_name' => 'Siti', 'last_name' => 'Nur']);

    Livewire::test('pages::wards.show', ['ward' => $ward])
        ->set('search', 'Ahmad')
        ->assertSee($match->full_name)
        ->assertDontSee($other->full_name);
});

test('ward patient list counts patients into stable, moderate, and critical KPIs', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create();
    Patient::factory()->lowRisk()->create(['ward_id' => $ward->id]);
    Patient::factory()->count(2)->highRisk()->create(['ward_id' => $ward->id]);

    $response = Livewire::test('pages::wards.show', ['ward' => $ward]);

    expect($response->viewData('patientCount'))->toBe(3);
    expect($response->viewData('stableCount'))->toBe(1);
    expect($response->viewData('criticalCount'))->toBe(2);
});

test('ward patient list shows bed number and reconciliation status', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create();
    $withBed = Patient::factory()->create(['ward_id' => $ward->id, 'bed_no' => 'E-01']);
    Reconciliation::factory()->completed()->create(['patient_id' => $withBed->id]);

    $noReconciliation = Patient::factory()->create(['ward_id' => $ward->id]);

    Livewire::test('pages::wards.show', ['ward' => $ward])
        ->assertSee('E-01')
        ->assertSee('Done')
        ->assertSee('Not started');
});

test('discharged patients are excluded from the ward patient list and KPIs by default', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create();
    Patient::factory()->create(['ward_id' => $ward->id, 'first_name' => 'Ahmad']);
    Patient::factory()->discharged()->create(['ward_id' => $ward->id, 'first_name' => 'Siti']);

    $response = Livewire::test('pages::wards.show', ['ward' => $ward])
        ->assertSee('Ahmad')
        ->assertDontSee('Siti');

    expect($response->viewData('patientCount'))->toBe(1);
});

test('discharged patients can be revealed with the include-discharged filter', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create();
    Patient::factory()->discharged()->create(['ward_id' => $ward->id, 'first_name' => 'Siti']);

    Livewire::test('pages::wards.show', ['ward' => $ward])
        ->assertDontSee('Siti')
        ->set('includeDischarged', true)
        ->assertSee('Siti');
});

test('ward patient list can be exported as csv', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create(['name' => 'Ward 1']);
    Patient::factory()->create(['ward_id' => $ward->id, 'first_name' => 'Ahmad', 'bed_no' => 'E-01']);

    Livewire::test('pages::wards.show', ['ward' => $ward])
        ->call('exportList')
        ->assertFileDownloaded('ward-1-patients.csv');
});
