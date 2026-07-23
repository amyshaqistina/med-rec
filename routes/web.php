<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

require __DIR__.'/settings.php';
require __DIR__.'/wards.php';
require __DIR__.'/patients.php';
require __DIR__.'/reconciliations.php';
