<?php

namespace App\Repositories\Contracts;

use App\Models\Institution\InstitutionAssessment;
use App\Models\National\NationalAssessment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AnalyticsRepositoryInterface
{
    public function getPublishedInstitutionExams(?int $organizationId, bool $canViewAllOrganizations): Collection;

    public function getPublishedNationalExams(): Collection;

    public function getActiveInstitutionOrganizations(): Collection;

    public function findInstitutionAssessmentForAnalytics(int $examId, bool $withChoices = false): InstitutionAssessment;

    public function findNationalAssessmentForAnalytics(int $examId, bool $withChoices = false): NationalAssessment;

    public function getCompletedInstitutionAttemptsForExam(int $examId, ?int $organizationId, bool $canViewAllOrganizations, array $filters, bool $withAnswers = false): Collection;

    public function getCompletedNationalAttemptsForExam(int $examId, array $filters, bool $withAnswers = false): Collection;

    public function paginateQuestionBankAnalytics(?int $organizationId, bool $isNational, array $filters, string $sortBy, string $sortOrder, int $perPage = 20): LengthAwarePaginator;

    public function getQuestionBankSummary(?int $organizationId, bool $isNational): array;

    public function getQuestionBankTopics(?int $organizationId, bool $isNational): Collection;
}
