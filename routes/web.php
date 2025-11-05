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
        ->middleware('permission:view-residents')
        ->name('residents.index');
    Route::get('residents/export', [App\Http\Controllers\ResidentController::class, 'export'])
        ->middleware('permission:export-residents')
        ->name('residents.export');
    Route::post('residents', [App\Http\Controllers\ResidentController::class, 'store'])
        ->middleware('permission:create-residents')
        ->name('residents.store');
    Route::get('residents/{resident}', [App\Http\Controllers\ResidentController::class, 'show'])
        ->middleware('permission:view-residents')
        ->name('residents.show');
    Route::patch('residents/{resident}', [App\Http\Controllers\ResidentController::class, 'update'])
        ->middleware('permission:edit-residents')
        ->name('residents.update');
    Route::delete('residents/{resident}', [App\Http\Controllers\ResidentController::class, 'destroy'])
        ->middleware('permission:delete-residents')
        ->name('residents.destroy');
    Route::post('residents/{resident}/organizations', [App\Http\Controllers\ResidentController::class, 'attachOrganization'])
        ->middleware('permission:edit-residents')
        ->name('residents.organizations.attach');
    Route::delete('residents/{resident}/organizations', [App\Http\Controllers\ResidentController::class, 'detachOrganization'])
        ->middleware('permission:edit-residents')
        ->name('residents.organizations.detach');

    // Institutions
    Route::get('institutions', [App\Http\Controllers\OrganizationController::class, 'index'])
        ->middleware('permission:view-institutions')
        ->name('institutions.index');
    Route::get('institutions/export', [App\Http\Controllers\OrganizationController::class, 'export'])
        ->middleware('permission:export-institutions')
        ->name('institutions.export');
    Route::post('institutions', [App\Http\Controllers\OrganizationController::class, 'store'])
        ->middleware('permission:create-institutions')
        ->name('institutions.store');
    Route::patch('institutions/{organization}', [App\Http\Controllers\OrganizationController::class, 'update'])
        ->middleware('permission:edit-institutions')
        ->name('institutions.update');
    Route::delete('institutions/{organization}', [App\Http\Controllers\OrganizationController::class, 'destroy'])
        ->middleware('permission:delete-institutions')
        ->name('institutions.destroy');

    // Staff
    Route::get('staff', [App\Http\Controllers\StaffController::class, 'index'])
        ->middleware('permission:view-staff')
        ->name('staff.index');
    Route::get('staff/export', [App\Http\Controllers\StaffController::class, 'export'])
        ->middleware('permission:export-staff')
        ->name('staff.export');
    Route::post('staff', [App\Http\Controllers\StaffController::class, 'store'])
        ->middleware('permission:create-staff')
        ->name('staff.store');
    Route::get('staff/{staff}', [App\Http\Controllers\StaffController::class, 'show'])
        ->middleware('permission:view-staff')
        ->name('staff.show');
    Route::patch('staff/{staff}', [App\Http\Controllers\StaffController::class, 'update'])
        ->middleware('permission:edit-staff')
        ->name('staff.update');
    Route::delete('staff/{staff}', [App\Http\Controllers\StaffController::class, 'destroy'])
        ->middleware('permission:delete-staff')
        ->name('staff.destroy');

    // Organization switching
    Route::post('organization/{organization}/switch', [App\Http\Controllers\OrganizationController::class, 'switch'])
        ->name('organization.switch');
});

require __DIR__ . '/settings.php';
