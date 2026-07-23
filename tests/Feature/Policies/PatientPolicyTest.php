<?php

use App\Enums\UserRole;
use App\Models\MedicationHistory;
use App\Models\Patient;
use App\Models\Reconciliation;
use App\Models\User;

test('every role can view patients, medication history, and reconciliations', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $patient = Patient::factory()->create();
    $medicationHistory = MedicationHistory::factory()->create(['patient_id' => $patient->id]);
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);

    expect($user->can('viewAny', Patient::class))->toBeTrue();
    expect($user->can('view', $patient))->toBeTrue();
    expect($user->can('viewAny', MedicationHistory::class))->toBeTrue();
    expect($user->can('view', $medicationHistory))->toBeTrue();
    expect($user->can('viewAny', Reconciliation::class))->toBeTrue();
    expect($user->can('view', $reconciliation))->toBeTrue();
})->with('allRoles');

test('technician, pharmacist, manager, and admin can create and update patients, medication history, and reconciliations', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $patient = Patient::factory()->create();
    $medicationHistory = MedicationHistory::factory()->create(['patient_id' => $patient->id]);
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);

    expect($user->can('create', Patient::class))->toBeTrue();
    expect($user->can('update', $patient))->toBeTrue();
    expect($user->can('create', MedicationHistory::class))->toBeTrue();
    expect($user->can('update', $medicationHistory))->toBeTrue();
    expect($user->can('create', Reconciliation::class))->toBeTrue();
    expect($user->can('update', $reconciliation))->toBeTrue();
})->with('writerRoles');

test('physician and nurse cannot create or update patients, medication history, or reconciliations', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $patient = Patient::factory()->create();
    $medicationHistory = MedicationHistory::factory()->create(['patient_id' => $patient->id]);
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);

    expect($user->can('create', Patient::class))->toBeFalse();
    expect($user->can('update', $patient))->toBeFalse();
    expect($user->can('create', MedicationHistory::class))->toBeFalse();
    expect($user->can('update', $medicationHistory))->toBeFalse();
    expect($user->can('create', Reconciliation::class))->toBeFalse();
    expect($user->can('update', $reconciliation))->toBeFalse();
})->with('viewOnlyRoles');

test('only pharmacist, manager, and admin can assess discrepancies on a reconciliation', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);

    foreach ([UserRole::Pharmacist, UserRole::Manager, UserRole::Admin] as $role) {
        $user = User::factory()->create(['role' => $role]);
        expect($user->can('pharmacistAssess', $reconciliation))->toBeTrue();
    }

    foreach ([UserRole::Technician, UserRole::Physician, UserRole::Nurse] as $role) {
        $user = User::factory()->create(['role' => $role]);
        expect($user->can('pharmacistAssess', $reconciliation))->toBeFalse();
    }
});

test('superuser bypasses every policy check', function () {
    $user = User::factory()->superuser()->create();
    $patient = Patient::factory()->create();
    $medicationHistory = MedicationHistory::factory()->create(['patient_id' => $patient->id]);
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);

    expect($user->can('create', Patient::class))->toBeTrue();
    expect($user->can('update', $patient))->toBeTrue();
    expect($user->can('delete', $patient))->toBeTrue();
    expect($user->can('create', MedicationHistory::class))->toBeTrue();
    expect($user->can('update', $medicationHistory))->toBeTrue();
    expect($user->can('create', Reconciliation::class))->toBeTrue();
    expect($user->can('update', $reconciliation))->toBeTrue();
    expect($user->can('pharmacistAssess', $reconciliation))->toBeTrue();
});

dataset('allRoles', fn () => UserRole::cases());
dataset('writerRoles', fn () => [UserRole::Technician, UserRole::Pharmacist, UserRole::Manager, UserRole::Admin]);
dataset('viewOnlyRoles', fn () => [UserRole::Physician, UserRole::Nurse]);
