<?php

namespace App\Actions\NationalAssessments;

use App\Imports\NationalQuestionsImport;
use App\Models\National\NationalAssessment;
use Maatwebsite\Excel\Facades\Excel;

class ImportNationalQuestionsAction
{
    public function execute(NationalAssessment $assessment, mixed $file, mixed $user): NationalQuestionsImport
    {
        $import = new NationalQuestionsImport($assessment->id, $user);
        Excel::import($import, $file);

        return $import;
    }
}
