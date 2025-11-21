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

// Custom logout route that handles Inertia properly
Route::post('logout', function (Request $request) {
    // Logout the user if authenticated
    if ($request->user()) {
        \Illuminate\Support\Facades\Auth::logout();
    }

    // Invalidate and regenerate session
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    // Handle Inertia requests
    if ($request->header('X-Inertia')) {
        return Inertia::location('/login');
    }

    return redirect('/login');
})->middleware('web')->name('logout');

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
    Route::post('exams/{type}/{attempt}/update-metadata', [App\Http\Controllers\ResidentExamController::class, 'updateMetadata'])
        ->name('exams.update-metadata');
    Route::get('exams/{type}/{attempt}/session-info', [App\Http\Controllers\ResidentExamController::class, 'getSessionInfo'])
        ->name('exams.session-info');
    Route::get('exams/{type}/{attempt}/current-ip', [App\Http\Controllers\ResidentExamController::class, 'getCurrentIp'])
        ->name('exams.current-ip');

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
    Route::get('staff/{staff}/logs', [App\Http\Controllers\StaffController::class, 'logs'])
        ->middleware('permission:view-staff')
        ->name('staff.logs');
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

    // Events & Conventions
    Route::get('events', [App\Http\Controllers\EventController::class, 'index'])
        ->name('events.index');
    Route::get('events/manage', [App\Http\Controllers\EventController::class, 'manage'])
        ->middleware('permission:view-events')
        ->name('events.manage');
    Route::post('events', [App\Http\Controllers\EventController::class, 'store'])
        ->middleware('permission:create-events')
        ->name('events.store');
    Route::get('events/my-registrations', [App\Http\Controllers\EventController::class, 'myRegistrations'])
        ->name('events.my-registrations');
    Route::get('events/{event}', [App\Http\Controllers\EventController::class, 'show'])
        ->name('events.show');
    Route::patch('events/{event}', [App\Http\Controllers\EventController::class, 'update'])
        ->middleware('permission:edit-events')
        ->name('events.update');
    Route::delete('events/{event}', [App\Http\Controllers\EventController::class, 'destroy'])
        ->middleware('permission:delete-events')
        ->name('events.destroy');
    Route::post('events/{event}/register', [App\Http\Controllers\EventController::class, 'register'])
        ->name('events.register');
    Route::post('events/{event}/cancel-registration', [App\Http\Controllers\EventController::class, 'cancelRegistration'])
        ->name('events.cancel-registration');
    Route::get('events/{event}/attendees', [App\Http\Controllers\EventController::class, 'attendees'])
        ->middleware('permission:view-events')
        ->name('events.attendees');
    Route::get('events/{event}/meeting-attendance', [App\Http\Controllers\EventController::class, 'meetingAttendance'])
        ->middleware('permission:view-events')
        ->name('events.meeting-attendance');
    Route::post('events/{event}/registrations/{registration}/approve', [App\Http\Controllers\EventController::class, 'approveRegistration'])
        ->middleware('permission:edit-events')
        ->name('events.approve-registration');
    Route::post('events/{event}/meeting/join', [App\Http\Controllers\EventController::class, 'joinMeeting'])
        ->name('events.meeting.join');
    Route::post('events/{event}/meeting/heartbeat', [App\Http\Controllers\EventController::class, 'meetingHeartbeat'])
        ->name('events.meeting.heartbeat');
    Route::post('events/{event}/meeting/leave', [App\Http\Controllers\EventController::class, 'leaveMeeting'])
        ->name('events.meeting.leave');

    // My Grades (Resident's personal performance dashboard)
    Route::get('my-grades', [App\Http\Controllers\GradebookController::class, 'myGrades'])
        ->name('gradebook.my-grades');

    // Assignments (Training Officers create, Residents submit)
    Route::get('assignments', [App\Http\Controllers\AssignmentController::class, 'index'])
        ->middleware('permission:view-assignments')
        ->name('assignments.index');
    Route::get('assignments/create', [App\Http\Controllers\AssignmentController::class, 'create'])
        ->middleware('permission:create-assignments')
        ->name('assignments.create');
    Route::post('assignments', [App\Http\Controllers\AssignmentController::class, 'store'])
        ->middleware('permission:create-assignments')
        ->name('assignments.store');
    Route::get('assignments/{assignment}', [App\Http\Controllers\AssignmentController::class, 'show'])
        ->middleware('permission:view-assignments')
        ->name('assignments.show');
    Route::get('assignments/{assignment}/edit', [App\Http\Controllers\AssignmentController::class, 'edit'])
        ->middleware('permission:edit-assignments')
        ->name('assignments.edit');
    Route::patch('assignments/{assignment}', [App\Http\Controllers\AssignmentController::class, 'update'])
        ->middleware('permission:edit-assignments')
        ->name('assignments.update');
    Route::delete('assignments/{assignment}', [App\Http\Controllers\AssignmentController::class, 'destroy'])
        ->middleware('permission:delete-assignments')
        ->name('assignments.destroy');

    // API endpoint for fetching submissions (returns JSON)
    Route::get('api/assignments/{assignment}/submissions', [App\Http\Controllers\AssignmentController::class, 'getSubmissions'])
        ->middleware('permission:view-assignments')
        ->name('api.assignments.submissions');

    // Resident Assignment Portal
    Route::get('my-assignments', [App\Http\Controllers\AssignmentController::class, 'myAssignments'])
        ->name('assignments.my-assignments');
    Route::post('assignments/{assignment}/submit', [App\Http\Controllers\AssignmentController::class, 'storeSubmission'])
        ->name('assignments.submit.store');

    // Grading Interface (Training Officers)
    Route::get('submissions/{submission}/grade', [App\Http\Controllers\AssignmentController::class, 'grade'])
        ->middleware('permission:grade-assignments')
        ->name('submissions.grade');
    Route::post('submissions/{submission}/grade', [App\Http\Controllers\AssignmentController::class, 'saveGrade'])
        ->middleware('permission:grade-assignments')
        ->name('submissions.save-grade');
    Route::get('submission-files/{file}/download', [App\Http\Controllers\AssignmentController::class, 'downloadFile'])
        ->name('submission-files.download');

    // Assessment Reports (Staff Only - residents use "My Exams" to see their own results)
    Route::redirect('assessment-reports', '/assessment-reports/by-resident')->name('assessment-reports');
    Route::get('assessment-reports/by-resident', [App\Http\Controllers\AssessmentReportController::class, 'byResident'])
        ->middleware('permission:view-assessment-reports')
        ->name('assessment-reports.by-resident'); // Individual exam attempts
    Route::get('assessment-reports/by-performance', [App\Http\Controllers\GradebookController::class, 'index'])
        ->middleware('permission:view-assessment-reports')
        ->name('assessment-reports.by-performance'); // Aggregated resident performance
    Route::get('assessment-reports/resident/{resident}', [App\Http\Controllers\GradebookController::class, 'show'])
        ->middleware('permission:view-assessment-reports')
        ->name('assessment-reports.resident-detail'); // Detailed resident report
    Route::get('assessment-reports/live-monitor', [App\Http\Controllers\AssessmentReportController::class, 'liveMonitor'])
        ->middleware('permission:view-assessment-reports')
        ->name('assessment-reports.live-monitor'); // Real-time monitoring

    // Analytics
    Route::redirect('analytics', '/analytics/exam-analytics')->name('analytics');
    Route::get('analytics/exam-analytics', [App\Http\Controllers\AnalyticsController::class, 'examAnalytics'])
        ->middleware('permission:view-analytics')
        ->name('analytics.exam-analytics');
    Route::get('analytics/item-analysis', [App\Http\Controllers\AnalyticsController::class, 'itemAnalysis'])
        ->middleware('permission:view-analytics')
        ->name('analytics.item-analysis');
    Route::get('analytics/topic-performance', [App\Http\Controllers\AnalyticsController::class, 'topicPerformance'])
        ->middleware('permission:view-analytics')
        ->name('analytics.topic-performance');
    Route::get('analytics/question-bank', [App\Http\Controllers\AnalyticsController::class, 'questionBank'])
        ->middleware('permission:view-analytics')
        ->name('analytics.question-bank');
    Route::get('analytics/category-performance', [App\Http\Controllers\AnalyticsController::class, 'categoryPerformance'])
        ->middleware('permission:view-analytics')
        ->name('analytics.category-performance');
    Route::get('analytics/trends', [App\Http\Controllers\AnalyticsController::class, 'trends'])
        ->middleware('permission:view-analytics')
        ->name('analytics.trends');

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
    Route::post('assessments/{assessment}/duplicate', [App\Http\Controllers\InstitutionExamController::class, 'duplicate'])
        ->middleware('permission:create-assessments')
        ->name('assessments.duplicate');
    Route::post('assessments/{assessment}/questions', [App\Http\Controllers\InstitutionExamController::class, 'storeQuestions'])
        ->middleware('permission:edit-assessments')
        ->name('assessments.questions.store');
    Route::post('assessments/{assessment}/questions/save-one', [App\Http\Controllers\InstitutionExamController::class, 'saveOneQuestion'])
        ->middleware('permission:edit-assessments')
        ->name('assessments.questions.save-one');
    Route::delete('assessments/{assessment}/questions/{question}', [App\Http\Controllers\InstitutionExamController::class, 'deleteQuestion'])
        ->middleware('permission:edit-assessments')
        ->name('assessments.questions.delete');
    Route::get('assessments/questions/template', [App\Http\Controllers\InstitutionExamController::class, 'downloadTemplate'])
        ->middleware('permission:create-assessments')
        ->name('assessments.questions.template');
    Route::post('assessments/{assessment}/questions/preview', [App\Http\Controllers\QuestionImportController::class, 'preview'])
        ->middleware('permission:edit-assessments')
        ->name('assessments.questions.preview');
    Route::post('assessments/{assessment}/questions/import', [App\Http\Controllers\QuestionImportController::class, 'confirmImport'])
        ->middleware('permission:edit-assessments')
        ->name('assessments.questions.import');

    // National In-Service Exams
    // Back-compat: redirect old in-service paths to inservice-exams
    Route::redirect('in-service', '/inservice-exams')->middleware('permission:view-assessments');
    Route::redirect('in-service/create', '/inservice-exams/create')->middleware('role:System Admin|BOP');

    Route::get('inservice-exams', [App\Http\Controllers\NationalAssessmentController::class, 'index'])
        ->middleware('permission:view-assessments')
        ->name('inservice-exams.index');
    Route::get('inservice-exams/active', [App\Http\Controllers\NationalAssessmentController::class, 'active'])
        ->middleware('permission:view-assessments')
        ->name('inservice-exams.active');
    Route::get('inservice-exams/drafts', [App\Http\Controllers\NationalAssessmentController::class, 'drafts'])
        ->middleware('role:System Admin|BOP')
        ->name('inservice-exams.drafts');
    Route::get('inservice-exams/create', function () {
        return Inertia::render('inservice-exams/create');
    })->middleware('role:System Admin|BOP')->name('inservice-exams.create');
    Route::post('inservice-exams', [App\Http\Controllers\NationalAssessmentController::class, 'store'])
        ->middleware('role:System Admin|BOP')
        ->name('inservice-exams.store');
    Route::post('inservice-exams/{assessment}/questions', [App\Http\Controllers\NationalAssessmentController::class, 'storeQuestions'])
        ->middleware('role:System Admin|BOP')
        ->name('inservice-exams.questions.store');
    Route::get('inservice-exams/{assessment}/edit', [App\Http\Controllers\NationalAssessmentController::class, 'edit'])
        ->middleware('role:System Admin|BOP')
        ->name('inservice-exams.edit');
    Route::post('inservice-exams/{assessment}/questions/save-one', [App\Http\Controllers\NationalAssessmentController::class, 'saveOneQuestion'])
        ->middleware('role:System Admin|BOP')
        ->name('inservice-exams.questions.save-one');
    Route::delete('inservice-exams/{assessment}/questions/{question}', [App\Http\Controllers\NationalAssessmentController::class, 'deleteQuestion'])
        ->middleware('role:System Admin|BOP')
        ->name('inservice-exams.questions.delete');
    Route::post('inservice-exams/{assessment}/duplicate', [App\Http\Controllers\NationalAssessmentController::class, 'duplicate'])
        ->middleware('role:System Admin|BOP')
        ->name('inservice-exams.duplicate');
    Route::get('inservice-exams/{assessment}', [App\Http\Controllers\NationalAssessmentController::class, 'show'])
        ->middleware('permission:view-assessments')
        ->name('inservice-exams.show');
    Route::patch('inservice-exams/{assessment}', [App\Http\Controllers\NationalAssessmentController::class, 'update'])
        ->middleware('role:System Admin|BOP')
        ->name('inservice-exams.update');
    Route::delete('inservice-exams/{assessment}', [App\Http\Controllers\NationalAssessmentController::class, 'destroy'])
        ->middleware('role:System Admin|BOP')
        ->name('inservice-exams.destroy');
    Route::get('inservice-exams/questions/template', [App\Http\Controllers\NationalAssessmentController::class, 'downloadTemplate'])
        ->middleware('role:System Admin|BOP')
        ->name('inservice-exams.questions.template');
    Route::post('inservice-exams/{assessment}/questions/preview', [App\Http\Controllers\NationalAssessmentController::class, 'previewQuestions'])
        ->middleware('role:System Admin|BOP')
        ->name('inservice-exams.questions.preview');
    Route::post('inservice-exams/{assessment}/questions/import', [App\Http\Controllers\NationalAssessmentController::class, 'importQuestions'])
        ->middleware('role:System Admin|BOP')
        ->name('inservice-exams.questions.import');

    // Institution Exams (frontend pages)
    Route::redirect('institution-exams', '/institution-exams/active')->name('institution-exams');
    Route::get('institution-exams/active', [App\Http\Controllers\InstitutionExamController::class, 'index'])
        ->middleware('permission:view-assessments')
        ->name('institution-exams.active');
    Route::get('institution-exams/drafts', [App\Http\Controllers\InstitutionExamController::class, 'drafts'])
        ->middleware('permission:view-assessments')
        ->name('institution-exams.drafts');

    Route::get('question-bank', [App\Http\Controllers\QuestionBankController::class, 'index'])
        ->middleware(['permission:view-assessments', 'role:System Admin|Admin|Training Officer|BOP'])
        ->name('question-bank.index');
    Route::get('question-bank/list', [App\Http\Controllers\QuestionBankController::class, 'list'])
        ->middleware(['permission:view-assessments', 'role:System Admin|Admin|Training Officer|BOP'])
        ->name('question-bank.list');
    Route::post('question-bank', [App\Http\Controllers\QuestionBankController::class, 'store'])
        ->middleware(['permission:create-assessments', 'role:System Admin|Admin|Training Officer|BOP'])
        ->name('question-bank.store');
    Route::patch('question-bank/{question}', [App\Http\Controllers\QuestionBankController::class, 'update'])
        ->middleware(['permission:edit-assessments', 'role:System Admin|Admin|Training Officer|BOP'])
        ->name('question-bank.update');
    Route::delete('question-bank/{question}', [App\Http\Controllers\QuestionBankController::class, 'destroy'])
        ->middleware(['permission:delete-assessments', 'role:System Admin|Admin|Training Officer|BOP'])
        ->name('question-bank.destroy');
    Route::post('question-bank/{question}/approve', [App\Http\Controllers\QuestionBankController::class, 'approve'])
        ->middleware(['permission:edit-assessments', 'role:System Admin|Admin|Training Officer|BOP'])
        ->name('question-bank.approve');
    Route::get('question-bank/statistics', [App\Http\Controllers\QuestionBankController::class, 'statistics'])
        ->middleware(['permission:view-assessments', 'role:System Admin|Admin|Training Officer|BOP'])
        ->name('question-bank.statistics');
    Route::post('question-bank/preview-import', [App\Http\Controllers\QuestionBankController::class, 'previewImport'])
        ->middleware(['permission:create-assessments', 'role:System Admin|Admin|Training Officer|BOP'])
        ->name('question-bank.preview-import');
    Route::post('question-bank/import', [App\Http\Controllers\QuestionBankController::class, 'import'])
        ->middleware(['permission:create-assessments', 'role:System Admin|Admin|Training Officer|BOP'])
        ->name('question-bank.import');
    Route::post('assessments/{assessment}/questions/from-bank', [App\Http\Controllers\InstitutionExamController::class, 'addFromBank'])
        ->middleware(['permission:create-assessments', 'role:System Admin|Admin|Training Officer|BOP'])
        ->name('assessments.questions.from-bank');

    // In-Service Exams (frontend pages)
    Route::redirect('inservice-exams', '/inservice-exams/active')->name('inservice-exams');
    Route::get('inservice-exams/drafts', [App\Http\Controllers\NationalAssessmentController::class, 'drafts'])
        ->middleware('permission:view-assessments')
        ->name('inservice-exams.drafts');

    // Organization switching
    Route::post('organization/{organization}/switch', [App\Http\Controllers\OrganizationController::class, 'switch'])
        ->name('organization.switch');
});

require __DIR__.'/settings.php';
