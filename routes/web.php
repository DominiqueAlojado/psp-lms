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
    Route::get('residents/export', [App\Http\Controllers\ResidentController::class, 'export'])
        ->name('residents.export');
    Route::post('residents', [App\Http\Controllers\ResidentController::class, 'store'])
        ->name('residents.store');
    Route::get('residents/{resident}', [App\Http\Controllers\ResidentController::class, 'show'])
        ->name('residents.show');
    Route::patch('residents/{resident}', [App\Http\Controllers\ResidentController::class, 'update'])
        ->name('residents.update');
    Route::delete('residents/{resident}', [App\Http\Controllers\ResidentController::class, 'destroy'])
        ->name('residents.destroy');
    Route::post('residents/{resident}/organizations', [App\Http\Controllers\ResidentController::class, 'attachOrganization'])
        ->name('residents.organizations.attach');
    Route::delete('residents/{resident}/organizations', [App\Http\Controllers\ResidentController::class, 'detachOrganization'])
        ->name('residents.organizations.detach');

    // Institutions
    Route::get('institutions', [App\Http\Controllers\OrganizationController::class, 'index'])
        ->name('institutions.index');
    Route::get('institutions/export', [App\Http\Controllers\OrganizationController::class, 'export'])
        ->name('institutions.export');
    Route::post('institutions', [App\Http\Controllers\OrganizationController::class, 'store'])
        ->name('institutions.store');
    Route::patch('institutions/{organization}', [App\Http\Controllers\OrganizationController::class, 'update'])
        ->name('institutions.update');
    Route::delete('institutions/{organization}', [App\Http\Controllers\OrganizationController::class, 'destroy'])
        ->name('institutions.destroy');

    // Staff
    Route::get('staff', [App\Http\Controllers\StaffController::class, 'index'])
        ->name('staff.index');
    Route::get('staff/export', [App\Http\Controllers\StaffController::class, 'export'])
        ->name('staff.export');
    Route::post('staff', [App\Http\Controllers\StaffController::class, 'store'])
        ->name('staff.store');
    Route::get('staff/{staff}', [App\Http\Controllers\StaffController::class, 'show'])
        ->name('staff.show');
    Route::patch('staff/{staff}', [App\Http\Controllers\StaffController::class, 'update'])
        ->name('staff.update');
    Route::delete('staff/{staff}', [App\Http\Controllers\StaffController::class, 'destroy'])
        ->name('staff.destroy');

    // Organization switching
    Route::post('organization/{organization}/switch', [App\Http\Controllers\OrganizationController::class, 'switch'])
        ->name('organization.switch');
});

require __DIR__ . '/settings.php';
