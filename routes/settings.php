<?php

use App\Http\Controllers\Settings\OrganizationSettingsController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\RolesPermissionsController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::get('settings', function (Request $request) {
        $query = $request->query();
        $target = '/settings/profile';

        return ! empty($query)
            ? redirect($target.'?'.http_build_query($query))
            : redirect($target);
    });

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    Route::get('settings/organization', [OrganizationSettingsController::class, 'index'])
        ->middleware('permission:manage-organization-settings')
        ->name('organization.edit');
    Route::patch('settings/organization', [OrganizationSettingsController::class, 'update'])
        ->middleware('permission:manage-organization-settings')
        ->name('organization.update');
    Route::post('settings/organization/logo', [OrganizationSettingsController::class, 'uploadLogo'])
        ->middleware('permission:manage-organization-settings')
        ->name('organization.logo.upload');
    Route::delete('settings/organization/logo', [OrganizationSettingsController::class, 'deleteLogo'])
        ->middleware('permission:manage-organization-settings')
        ->name('organization.logo.delete');
    Route::patch('settings/organization/residents/{resident}', [OrganizationSettingsController::class, 'updateResident'])
        ->middleware('permission:manage-organization-settings')
        ->name('organization.residents.update');

    // Roles & Permissions Management
    Route::get('settings/roles-permissions', [RolesPermissionsController::class, 'index'])
        ->middleware('permission:manage-permissions')
        ->name('roles-permissions.index');

    // Roles
    Route::post('settings/roles', [RolesPermissionsController::class, 'storeRole'])
        ->middleware('permission:manage-permissions')
        ->name('roles.store');
    Route::patch('settings/roles/{role}', [RolesPermissionsController::class, 'updateRole'])
        ->middleware('permission:manage-permissions')
        ->name('roles.update');
    Route::delete('settings/roles/{role}', [RolesPermissionsController::class, 'deleteRole'])
        ->middleware('permission:manage-permissions')
        ->name('roles.delete');

    // Permissions
    Route::post('settings/permissions', [RolesPermissionsController::class, 'storePermission'])
        ->middleware('permission:manage-permissions')
        ->name('permissions.store');
    Route::patch('settings/permissions/{permission}', [RolesPermissionsController::class, 'updatePermission'])
        ->middleware('permission:manage-permissions')
        ->name('permissions.update');
    Route::delete('settings/permissions/{permission}', [RolesPermissionsController::class, 'deletePermission'])
        ->middleware('permission:manage-permissions')
        ->name('permissions.delete');

    Route::post('settings/permission-modules', [RolesPermissionsController::class, 'storePermissionModule'])
        ->middleware('permission:manage-permissions')
        ->name('permission-modules.store');
    Route::patch('settings/permission-modules', [RolesPermissionsController::class, 'renamePermissionModule'])
        ->middleware('permission:manage-permissions')
        ->name('permission-modules.rename');
    Route::delete('settings/permission-modules', [RolesPermissionsController::class, 'deletePermissionModule'])
        ->middleware('permission:manage-permissions')
        ->name('permission-modules.delete');

    // Assign permissions to role
    Route::post('settings/roles/{role}/permissions', [RolesPermissionsController::class, 'syncRolePermissions'])
        ->middleware('permission:manage-permissions')
        ->name('roles.permissions.sync');
});
