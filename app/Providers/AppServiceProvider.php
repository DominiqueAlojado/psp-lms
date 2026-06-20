<?php

namespace App\Providers;

use App\Repositories\Contracts\EventRegistrationRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\InstitutionAssessmentRepositoryInterface;
use App\Repositories\Contracts\InstitutionQuestionChoiceRepositoryInterface;
use App\Repositories\Contracts\InstitutionQuestionRepositoryInterface;
use App\Repositories\Contracts\LearningResourceRepositoryInterface;
use App\Repositories\Contracts\MeetingAttendanceRepositoryInterface;
use App\Repositories\Contracts\NationalAssessmentRepositoryInterface;
use App\Repositories\Contracts\NationalQuestionChoiceRepositoryInterface;
use App\Repositories\Contracts\NationalQuestionRepositoryInterface;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\Repositories\Contracts\QuestionBankChoiceRepositoryInterface;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Repositories\Contracts\QuestionBankStatisticRepositoryInterface;
use App\Repositories\Contracts\ResidentExamRepositoryInterface;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\SubmissionFileRepositoryInterface;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use App\Repositories\Eloquent\AssignmentRepository;
use App\Repositories\Eloquent\EventRegistrationRepository;
use App\Repositories\Eloquent\EventRepository;
use App\Repositories\Eloquent\AnnouncementRepository;
use App\Repositories\Eloquent\InstitutionAssessmentRepository;
use App\Repositories\Eloquent\InstitutionQuestionChoiceRepository;
use App\Repositories\Eloquent\InstitutionQuestionRepository;
use App\Repositories\Eloquent\LearningResourceRepository;
use App\Repositories\Eloquent\MeetingAttendanceRepository;
use App\Repositories\Eloquent\NationalAssessmentRepository;
use App\Repositories\Eloquent\NationalQuestionChoiceRepository;
use App\Repositories\Eloquent\NationalQuestionRepository;
use App\Repositories\Eloquent\OrganizationRepository;
use App\Repositories\Eloquent\QuestionBankChoiceRepository;
use App\Repositories\Eloquent\QuestionBankRepository;
use App\Repositories\Eloquent\QuestionBankStatisticRepository;
use App\Repositories\Eloquent\ResidentExamRepository;
use App\Repositories\Eloquent\ResidentRepository;
use App\Repositories\Eloquent\StaffRepository;
use App\Repositories\Eloquent\SubmissionFileRepository;
use App\Repositories\Eloquent\SubmissionRepository;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AnnouncementRepositoryInterface::class, AnnouncementRepository::class);
        $this->app->bind(AssignmentRepositoryInterface::class, AssignmentRepository::class);
        $this->app->bind(EventRegistrationRepositoryInterface::class, EventRegistrationRepository::class);
        $this->app->bind(EventRepositoryInterface::class, EventRepository::class);
        $this->app->bind(InstitutionAssessmentRepositoryInterface::class, InstitutionAssessmentRepository::class);
        $this->app->bind(InstitutionQuestionChoiceRepositoryInterface::class, InstitutionQuestionChoiceRepository::class);
        $this->app->bind(InstitutionQuestionRepositoryInterface::class, InstitutionQuestionRepository::class);
        $this->app->bind(LearningResourceRepositoryInterface::class, LearningResourceRepository::class);
        $this->app->bind(MeetingAttendanceRepositoryInterface::class, MeetingAttendanceRepository::class);
        $this->app->bind(NationalAssessmentRepositoryInterface::class, NationalAssessmentRepository::class);
        $this->app->bind(NationalQuestionChoiceRepositoryInterface::class, NationalQuestionChoiceRepository::class);
        $this->app->bind(NationalQuestionRepositoryInterface::class, NationalQuestionRepository::class);
        $this->app->bind(OrganizationRepositoryInterface::class, OrganizationRepository::class);
        $this->app->bind(QuestionBankChoiceRepositoryInterface::class, QuestionBankChoiceRepository::class);
        $this->app->bind(QuestionBankRepositoryInterface::class, QuestionBankRepository::class);
        $this->app->bind(QuestionBankStatisticRepositoryInterface::class, QuestionBankStatisticRepository::class);
        $this->app->bind(ResidentExamRepositoryInterface::class, ResidentExamRepository::class);
        $this->app->bind(ResidentRepositoryInterface::class, ResidentRepository::class);
        $this->app->bind(StaffRepositoryInterface::class, StaffRepository::class);
        $this->app->bind(SubmissionFileRepositoryInterface::class, SubmissionFileRepository::class);
        $this->app->bind(SubmissionRepositoryInterface::class, SubmissionRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS URLs in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
