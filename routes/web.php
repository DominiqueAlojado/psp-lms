<?php

use App\Http\Controllers\Admin\TenantController;
use App\Http\Middleware\EnsureSystemAdmin;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified', NeedsTenant::class, EnsureValidTenantSession::class])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

// Admin routes (system admin only, no tenant context needed)
Route::middleware(['auth', 'verified', EnsureSystemAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('tenants', TenantController::class);
    Route::post('tenants/{tenant}/setup-local', [TenantController::class, 'setupLocalDevelopment'])->name('tenants.setup-local');
});

require __DIR__.'/settings.php';
