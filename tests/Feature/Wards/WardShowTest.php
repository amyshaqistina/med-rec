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
    $bed = $ward->beds()->first();
    $withBed = Patient::factory()->create(['ward_id' => $ward->id, 'bed_id' => $bed->id]);
    Reconciliation::factory()->completed()->create(['patient_id' => $withBed->id]);

    $noReconciliation = Patient::factory()->create(['ward_id' => $ward->id]);

    Livewire::test('pages::wards.show', ['ward' => $ward])
        ->assertSee($bed->label())
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

test('ward patient list action button links to the patient details page', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create();
    $patient = Patient::factory()->create(['ward_id' => $ward->id]);

    $this->get(route('wards.show', $ward))
        ->assertOk()
        ->assertSee(route('patients.show', $patient), escape: false)
        ->assertDontSee(route('patients.edit', $patient), escape: false);
});

test('ward patient list can be exported as csv', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create(['name' => 'Ward 1']);
    Patient::factory()->create(['ward_id' => $ward->id, 'first_name' => 'Ahmad', 'bed_id' => $ward->beds()->first()->id]);

    Livewire::test('pages::wards.show', ['ward' => $ward])
        ->call('exportList')
        ->assertFileDownloaded('ward-1-patients.csv');
});

test('ward patient list shows a row for every bed, including empty ones', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create(['bed_capacity' => 3]);
    $bed = $ward->beds()->orderBy('bed_no')->first();
    $patient = Patient::factory()->create(['ward_id' => $ward->id, 'bed_id' => $bed->id, 'first_name' => 'Ahmad']);

    $response = Livewire::test('pages::wards.show', ['ward' => $ward])
        ->assertSee('Ahmad')
        ->assertSee('Occupied')
        ->assertSee('Empty');

    expect($response->viewData('rows'))->toHaveCount(3);
});

test('empty bed rows come after occupied ones', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create(['bed_capacity' => 3]);
    $beds = $ward->beds()->orderBy('bed_no')->get();
    // Occupy the last bed only, leaving beds 1 and 2 empty.
    Patient::factory()->create(['ward_id' => $ward->id, 'bed_id' => $beds->last()->id]);

    $rows = Livewire::test('pages::wards.show', ['ward' => $ward])->viewData('rows');

    expect($rows->pluck('bed.bed_no')->all())->toBe([3, 1, 2]);
});

test('empty bed rows are hidden while searching or filtering by risk', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create(['bed_capacity' => 3]);
    $bed = $ward->beds()->first();
    Patient::factory()->create(['ward_id' => $ward->id, 'bed_id' => $bed->id, 'first_name' => 'Ahmad']);

    Livewire::test('pages::wards.show', ['ward' => $ward])
        ->set('search', 'Ahmad')
        ->assertSee('Ahmad')
        ->assertDontSee('Empty');
});

test('patients admitted without a bed fill the free beds instead of adding extra rows', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create(['bed_capacity' => 12]);
    Patient::factory()->count(8)->create(['ward_id' => $ward->id]);

    $rows = Livewire::test('pages::wards.show', ['ward' => $ward])->viewData('rows');

    expect($rows)->toHaveCount(12);
    expect($rows->filter(fn ($row) => $row->patient)->pluck('bed.bed_no')->all())->toBe(range(1, 8));
    expect($rows->reject(fn ($row) => $row->patient)->pluck('bed.bed_no')->values()->all())->toBe(range(9, 12));
});

test('creating a ward automatically generates its beds', function () {
    $ward = Ward::factory()->create(['bed_capacity' => 5]);

    expect($ward->beds()->count())->toBe(5);
    expect($ward->beds()->pluck('bed_no')->sort()->values()->all())->toBe([1, 2, 3, 4, 5]);
});
