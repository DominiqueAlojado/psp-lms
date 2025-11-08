<?php

use Illuminate\Http\Request;
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

    // Resident Exams (for residents to view and take exams)
    Route::get('resident-exams', [App\Http\Controllers\ResidentExamController::class, 'index'])
        ->name('resident-exams.index');
    Route::get('exams/{type}/{id}/take', [App\Http\Controllers\ResidentExamController::class, 'take'])
        ->name('exams.take');
    Route::get('exams/{type}/{id}/results', [App\Http\Controllers\ResidentExamController::class, 'results'])
        ->name('exams.results');
    Route::get('exams/{type}/{id}/results-data', [App\Http\Controllers\ResidentExamController::class, 'resultsApi'])
        ->name('exams.results.api');
    Route::post('exams/{type}/{attempt}/save-answer', [App\Http\Controllers\ResidentExamController::class, 'saveAnswer'])
        ->name('exams.save-answer');
    Route::post('exams/{type}/{attempt}/submit', [App\Http\Controllers\ResidentExamController::class, 'submit'])
        ->name('exams.submit');
    Route::post('exams/{type}/{attempt}/log-session-change', [App\Http\Controllers\ResidentExamController::class, 'logSessionChange'])
        ->name('exams.log-session-change');
    Route::post('exams/{type}/{attempt}/log-activity', [App\Http\Controllers\ResidentExamController::class, 'logActivity'])
        ->name('exams.log-activity');

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

    // Topics
    Route::get('topics', [App\Http\Controllers\TopicController::class, 'index'])
        ->name('topics.index');
    Route::post('topics', [App\Http\Controllers\TopicController::class, 'store'])
        ->name('topics.store');

    // Learning Resources
    Route::get('resources', [App\Http\Controllers\ResourceController::class, 'index'])
        ->name('resources.index');
    Route::get('resources/manage', [App\Http\Controllers\ResourceController::class, 'manage'])
        ->middleware('permission:view-materials')
        ->name('resources.manage');
    Route::post('resources', [App\Http\Controllers\ResourceController::class, 'store'])
        // ->middleware('permission:upload-materials') // Temporarily disabled for testing
        ->name('resources.store');
    Route::patch('resources/{resource}', [App\Http\Controllers\ResourceController::class, 'update'])
        ->middleware('permission:edit-materials')
        ->name('resources.update');
    Route::delete('resources/{resource}', [App\Http\Controllers\ResourceController::class, 'destroy'])
        ->middleware('permission:delete-materials')
        ->name('resources.destroy');
    Route::get('resources/{resource}/download', [App\Http\Controllers\ResourceController::class, 'download'])
        ->name('resources.download');

    // Announcements
    Route::get('announcements', [App\Http\Controllers\AnnouncementController::class, 'index'])
        ->name('announcements.index');
    Route::get('announcements/manage', [App\Http\Controllers\AnnouncementController::class, 'manage'])
        ->middleware('permission:view-announcements')
        ->name('announcements.manage');
    Route::post('announcements', [App\Http\Controllers\AnnouncementController::class, 'store'])
        ->middleware('permission:create-announcements')
        ->name('announcements.store');
    Route::patch('announcements/{announcement}', [App\Http\Controllers\AnnouncementController::class, 'update'])
        ->middleware('permission:edit-announcements')
        ->name('announcements.update');
    Route::delete('announcements/{announcement}', [App\Http\Controllers\AnnouncementController::class, 'destroy'])
        ->middleware('permission:delete-announcements')
        ->name('announcements.destroy');
    Route::post('announcements/{announcement}/view', [App\Http\Controllers\AnnouncementController::class, 'markAsViewed'])
        ->name('announcements.view');

    // Assessment Reports
    Route::redirect('assessment-reports', '/assessment-reports/by-resident')->name('assessment-reports');
    Route::get('assessment-reports/by-resident', [App\Http\Controllers\AssessmentReportController::class, 'byResident'])
        ->middleware('permission:view-assessments')
        ->name('assessment-reports.by-resident');
    Route::get('assessment-reports/tab2', function () {
        return Inertia::render('assessment-reports/tab2');
    })->middleware('permission:view-assessments')->name('assessment-reports.tab2');

    // Institution Exams
    Route::get('assessments', [App\Http\Controllers\InstitutionExamController::class, 'index'])
        ->middleware('permission:view-assessments')
        ->name('assessments.index');
    Route::get('institution-exams/create', function (Request $request) {
        return Inertia::render('institution-exams/create', [
            'assessmentId' => $request->query('assessment_id'),
        ]);
    })->middleware('permission:create-assessments')->name('institution-exams.create');
    Route::get('institution-exams/{assessment}/edit', [App\Http\Controllers\InstitutionExamController::class, 'edit'])
        ->middleware('permission:edit-assessments')
        ->name('institution-exams.edit');
    Route::post('assessments', [App\Http\Controllers\InstitutionExamController::class, 'store'])
        ->middleware('permission:create-assessments')
        ->name('assessments.store');
    Route::get('assessments/{assessment}', [App\Http\Controllers\InstitutionExamController::class, 'show'])
        ->middleware('permission:view-assessments')
        ->name('assessments.show');
    Route::patch('assessments/{assessment}', [App\Http\Controllers\InstitutionExamController::class, 'update'])
        ->middleware('permission:edit-assessments')
        ->name('assessments.update');
    Route::delete('assessments/{assessment}', [App\Http\Controllers\InstitutionExamController::class, 'destroy'])
        ->middleware('permission:delete-assessments')
        ->name('assessments.destroy');
    Route::post('assessments/{assessment}/questions', [App\Http\Controllers\InstitutionExamController::class, 'storeQuestions'])
        ->middleware('permission:edit-assessments')
        ->name('assessments.questions.store');
    Route::post('assessments/{assessment}/questions/save-one', [App\Http\Controllers\InstitutionExamController::class, 'saveOneQuestion'])
        ->middleware('permission:edit-assessments')
        ->name('assessments.questions.save-one');
    Route::delete('assessments/{assessment}/questions/{question}', [App\Http\Controllers\InstitutionExamController::class, 'deleteQuestion'])
        ->middleware('permission:edit-assessments')
        ->name('assessments.questions.delete');

    // National In-Service Exams
    Route::get('in-service', [App\Http\Controllers\NationalAssessmentController::class, 'index'])
        ->middleware('permission:view-assessments')
        ->name('in-service.index');
    Route::get('in-service/create', function () {
        return Inertia::render('in-service/create');
    })->middleware('role:System Admin|BOP')->name('in-service.create');
    Route::post('in-service', [App\Http\Controllers\NationalAssessmentController::class, 'store'])
        ->middleware('role:System Admin|BOP')
        ->name('in-service.store');
    Route::get('in-service/{assessment}', [App\Http\Controllers\NationalAssessmentController::class, 'show'])
        ->middleware('permission:view-assessments')
        ->name('in-service.show');
    Route::patch('in-service/{assessment}', [App\Http\Controllers\NationalAssessmentController::class, 'update'])
        ->middleware('role:System Admin|BOP')
        ->name('in-service.update');
    Route::delete('in-service/{assessment}', [App\Http\Controllers\NationalAssessmentController::class, 'destroy'])
        ->middleware('role:System Admin|BOP')
        ->name('in-service.destroy');

    // Institution Exams (frontend pages)
    Route::redirect('institution-exams', '/institution-exams/active')->name('institution-exams');
    Route::get('institution-exams/active', [App\Http\Controllers\InstitutionExamController::class, 'index'])
        ->middleware('permission:view-assessments')
        ->name('institution-exams.active');
    Route::get('institution-exams/drafts', [App\Http\Controllers\InstitutionExamController::class, 'drafts'])
        ->middleware('permission:view-assessments')
        ->name('institution-exams.drafts');

    // In-Service Exams (frontend pages)
    Route::redirect('inservice-exams', '/inservice-exams/active')->name('inservice-exams');
    Route::get('inservice-exams/active', function () {
        return Inertia::render('inservice-exams/active');
    })->middleware('permission:view-assessments')->name('inservice-exams.active');

    // Organization switching
    Route::post('organization/{organization}/switch', [App\Http\Controllers\OrganizationController::class, 'switch'])
        ->name('organization.switch');
});

require __DIR__.'/settings.php';
