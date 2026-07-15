<?php

namespace App\Services;

use App\Actions\Assignments\CreateSubmissionAction;
use App\Actions\Assignments\CreateSubmissionFileAction;
use App\Actions\Assignments\IncrementSubmissionFileDownloadAction;
use App\Actions\Assignments\UpdateSubmissionAction;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssignmentSubmissionService
{
    public function __construct(
        private readonly CreateSubmissionAction $createSubmissionAction,
        private readonly UpdateSubmissionAction $updateSubmissionAction,
        private readonly CreateSubmissionFileAction $createSubmissionFileAction,
        private readonly IncrementSubmissionFileDownloadAction $incrementSubmissionFileDownloadAction,
    ) {}

    public function createSubmission(User $user, Assignment $assignment, array $validated, array $files): Submission
    {
        $resident = $user->resident;

        $submission = DB::transaction(function () use ($assignment, $resident, $user, $validated) {
            Assignment::query()
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->first();

            $latestSubmissionNumber = Submission::query()
                ->where('assignment_id', $assignment->id)
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->max('submission_number');

            $submission = $this->createSubmissionAction->execute([
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
                'organization_id' => $user->currentOrganization?->id,
                'year_level' => $resident?->year_level,
                'submission_text' => $validated['submission_text'] ?? null,
                'submitted_at' => now(),
                'max_score' => $assignment->max_score,
                'status' => 'submitted',
                'submission_number' => ((int) $latestSubmissionNumber) + 1,
            ]);

            $submission->load('assignment');
            $submission->calculateLateDays();

            return $submission;
        });

        foreach ($files as $file) {
            $this->storeSubmissionFile($submission, $file);
        }

        return $submission;
    }

    public function gradeSubmission(User $user, Submission $submission, array $validated): bool
    {
        return $this->updateSubmissionAction->execute($submission, [
            'score' => $validated['score'],
            'grader_feedback' => $validated['grader_feedback'] ?? null,
            'status' => 'graded',
            'graded_by' => $user->id,
            'graded_at' => now(),
        ]);
    }

    public function incrementDownloadCount(SubmissionFile $file): void
    {
        $this->incrementSubmissionFileDownloadAction->execute($file);
    }

    private function storeSubmissionFile(Submission $submission, UploadedFile $file): void
    {
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('submissions', $fileName, 'public');

        $this->createSubmissionFileAction->execute([
            'submission_id' => $submission->id,
            'file_name' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }
}
