<?php

namespace App\Actions\InstitutionExams;

use App\Imports\QuestionsImport;
use App\Models\Institution\InstitutionAssessment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class ImportInstitutionAssessmentQuestionsAction
{
    /**
     * @return array{success_count:int, errors:array<int,string>}
     */
    public function execute(
        InstitutionAssessment $assessment,
        UploadedFile $file,
        User $user,
    ): array {
        $import = new QuestionsImport($assessment->id, $assessment->organization_id, $user);
        Excel::import($import, $file);

        return [
            'success_count' => $import->getSuccessCount(),
            'errors' => $import->getErrors(),
        ];
    }
}
