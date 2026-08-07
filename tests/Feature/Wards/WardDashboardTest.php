<?php

use App\Models\Patient;
use App\Models\User;
use App\Models\Ward;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('dashboard shows ward stats and lists every ward', function () {
    $this->actingAs(User::factory()->create());

    $wardA = Ward::factory()->create(['name' => 'Ward 1', 'department' => 'Emergency Ward', 'bed_capacity' => 10]);
    $wardB = Ward::factory()->create(['name' => 'Ward 2', 'department' => 'General Ward', 'bed_capacity' => 10]);

    Patient::factory()->count(3)->create(['ward_id' => $wardA->id]);
    Patient::factory()->count(2)->create(['ward_id' => $wardB->id]);

    $response = Livewire::test('pages::wards.index')
        ->assertSee('Ward 1')
        ->assertSee('Emergency Ward')
        ->assertSee('Ward 2');

    expect($response->viewData('totalPatients'))->toBe(5);
    expect($response->viewData('activeWards'))->toBe(2);
    expect($response->viewData('availableBeds'))->toBe(15);
});

test('discharged patients no longer count toward ward occupancy', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create(['name' => 'Ward 1', 'bed_capacity' => 10]);

    Patient::factory()->count(2)->create(['ward_id' => $ward->id]);
    Patient::factory()->discharged()->create(['ward_id' => $ward->id]);

    $response = Livewire::test('pages::wards.index');

    expect($response->viewData('totalPatients'))->toBe(2);
    expect($response->viewData('availableBeds'))->toBe(8);
});

test('clicking a ward navigates to its patient list', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create(['name' => 'Ward 3']);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('wards.show', $ward), escape: false);
});
