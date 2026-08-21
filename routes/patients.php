<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('patients', 'pages::patients.index')->name('patients.index');
    Route::livewire('patients/create', 'pages::patients.create')->name('patients.create');
    Route::livewire('patients/{patient}', 'pages::patients.show')->name('patients.show');
    Route::livewire('patients/{patient}/edit', 'pages::patients.edit')->name('patients.edit');
    Route::livewire('patients/{patient}/medication-history', 'pages::patients.medication-history')->name('patients.medication-history');
    Route::livewire('patients/{patient}/medication-history/list', 'pages::patients.medication-history-index')->name('patients.medication-history.index');
    Route::livewire('patients/{patient}/medication-history/create', 'pages::patients.medication-history-create')->name('patients.medication-history.create');
    Route::livewire('patients/{patient}/medication-history/{medicationHistory}/edit', 'pages::patients.medication-history-edit')->name('patients.medication-history.edit');
    Route::livewire('patients/{patient}/lab-results', 'pages::patients.lab-results')->name('patients.lab-results');
});
