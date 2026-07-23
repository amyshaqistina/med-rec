<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::wards.index')->name('dashboard');
    Route::livewire('wards/{ward}', 'pages::wards.show')->name('wards.show');
});
