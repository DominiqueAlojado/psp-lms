<?php

namespace App\Services;

use App\Actions\QuestionBank\ApproveQuestionBankQuestionAction;
use App\Actions\QuestionBank\CreateQuestionBankQuestionAction;
use App\Actions\QuestionBank\DeleteQuestionBankQuestionAction;
use App\Actions\QuestionBank\UpdateQuestionBankQuestionAction;
use App\Models\QuestionBank;
use App\Models\User;
use App\Repositories\Contracts\QuestionBankChoiceRepositoryInterface;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Repositories\Contracts\QuestionBankStatisticRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class QuestionBankManagementService
{
    public function __construct(
        private readonly CreateQuestionBankQuestionAction $createQuestionBankQuestionAction,
        private readonly UpdateQuestionBankQuestionAction $updateQuestionBankQuestionAction,
        private readonly DeleteQuestionBankQuestionAction $deleteQuestionBankQuestionAction,
        private readonly ApproveQuestionBankQuestionAction $approveQuestionBankQuestionAction,
        private readonly QuestionBankChoiceRepositoryInterface $questionBankChoiceRepository,
        private readonly QuestionBankStatisticRepositoryInterface $questionBankStatisticRepository,
        private readonly QuestionBankRepositoryInterface $questionBankRepository,
        private readonly QuestionBankReadService $questionBankReadService,
    ) {}

    public function create(User $user, array $validated, ?UploadedFile $image = null): QuestionBank
    {
        $context = $this->questionBankReadService->resolveScopeContext($user);

        $question = $this->createQuestionBankQuestionAction->execute([
            ...$validated,
            'organization_id' => $context['organizationId'],
            'owner_type' => $context['isNational'] ? 'national' : 'institution',
            'created_by' => $user->id,
            'image_path' => $image?->store('question-images', 'public'),
        ]);

        $this->questionBankStatisticRepository->initializeForQuestion(
            $question,
            $context['scope'],
            $context['isNational'] ? null : $context['organizationId']
        );
        $this->questionBankChoiceRepository->createMany($question, $validated['choices']);

        return $question->load('choices');
    }

    public function update(QuestionBank $question, array $validated, ?UploadedFile $image = null): array
    {
        $attributes = $validated;
        $imageChanged = $image !== null;

        if ($imageChanged) {
            if ($question->image_path && Storage::disk('public')->exists($question->image_path)) {
                Storage::disk('public')->delete($question->image_path);
            }
            $attributes['image_path'] = $image->store('question-images', 'public');
        }

        $this->updateQuestionBankQuestionAction->execute($question, $attributes);
        $this->questionBankChoiceRepository->replaceForQuestion($question, $validated['choices']);

        return [
            'attributes' => $attributes,
            'imageChanged' => $imageChanged,
        ];
    }

    public function delete(QuestionBank $question): bool
    {
        return $this->deleteQuestionBankQuestionAction->execute($question);
    }

    public function approve(QuestionBank $question, int $approvedBy): bool
    {
        return $this->approveQuestionBankQuestionAction->execute($question, $approvedBy);
    }

    public function canAccess(User $user, QuestionBank $question): bool
    {
        $context = $this->questionBankReadService->resolveScopeContext($user);

        if ($question->owner_type === 'national') {
            return $context['isNational'];
        }

        return $question->organization_id === $context['organizationId'];
    }

    public function isUsedInAssessments(QuestionBank $question): bool
    {
        return $this->questionBankRepository->assessmentsCount($question) > 0;
    }
}
