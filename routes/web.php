<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Residents
    Route::get('residents', [App\Http\Controllers\ResidentController::class, 'index'])
        ->name('residents.index');
    Route::get('residents/{resident}', [App\Http\Controllers\ResidentController::class, 'show'])
        ->name('residents.show');

    // Organization switching
    Route::post('organization/{organization}/switch', [App\Http\Controllers\OrganizationController::class, 'switch'])
        ->name('organization.switch');
});

require __DIR__.'/settings.php';
