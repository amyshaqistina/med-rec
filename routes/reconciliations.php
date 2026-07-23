<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('reconciliations', 'pages::reconciliations.index')->name('reconciliations.index');
    Route::livewire('patients/{patient}/reconciliations/create', 'pages::reconciliations.create')->name('reconciliations.create');
    Route::livewire('reconciliations/{reconciliation}', 'pages::reconciliations.show')->name('reconciliations.show');
});
