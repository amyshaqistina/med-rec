<?php

use App\Http\Controllers\Admin\RegisterUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:register-users'])->group(function () {
    Route::get('admin/users/create', [RegisterUserController::class, 'create'])->name('admin.users.create');
    Route::post('admin/users', [RegisterUserController::class, 'store'])->name('admin.users.store');
});
