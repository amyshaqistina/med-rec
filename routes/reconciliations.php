<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('reconciliations', 'pages::reconciliations.index')->name('reconciliations.index');
    Route::livewire('reconciliations/{reconciliation}', 'pages::reconciliations.show')->name('reconciliations.show');
});
