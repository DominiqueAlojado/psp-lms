<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Topic;
use App\Services\InstitutionAssessmentQuestionPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class InstitutionAssessmentQuestionPreviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_previews_valid_question_rows_with_topic_status(): void
    {
        $service = app(InstitutionAssessmentQuestionPreviewService::class);

        $organization = Organization::create([
            'name' => 'Alpha Hospital',
            'slug' => 'alpha-hospital',
            'type' => 'institution',
            'is_active' => true,
        ]);

        Topic::create([
            'name' => 'Anatomy',
            'slug' => 'anatomy',
            'organization_id' => $organization->id,
            'is_global' => false,
        ]);

        $assessment = new \App\Models\Institution\InstitutionAssessment([
            'organization_id' => $organization->id,
        ]);

        $csv = <<<CSV
question_text,type,points,topic_optional,explanation_optional,choice_1_correct_answer,choice_2,choice_3,choice_4
Sample question,multiple_choice,1,Anatomy,Why,A,B,C,D
CSV;

        $file = UploadedFile::fake()->createWithContent('questions.csv', $csv);

        $result = $service->preview($assessment, $file);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['total_valid']);
        $this->assertSame('Sample question', $result['questions'][0]['question_text']);
        $this->assertTrue($result['questions'][0]['topic']['exists']);
        $this->assertFalse($result['questions'][0]['topic']['will_create']);
    }

    public function test_it_reports_preview_errors_for_invalid_rows(): void
    {
        $service = app(InstitutionAssessmentQuestionPreviewService::class);

        $organization = Organization::create([
            'name' => 'Alpha Hospital',
            'slug' => 'alpha-hospital',
            'type' => 'institution',
            'is_active' => true,
        ]);

        $assessment = new \App\Models\Institution\InstitutionAssessment([
            'organization_id' => $organization->id,
        ]);

        $csv = <<<CSV
question_text,type,points,topic_optional,explanation_optional,choice_1_correct_answer,choice_2,choice_3,choice_4
,multiple_choice,1,Anatomy,Why,A,B,C,D
CSV;

        $file = UploadedFile::fake()->createWithContent('questions.csv', $csv);

        $result = $service->preview($assessment, $file);

        $this->assertSame(0, $result['total_valid']);
        $this->assertSame(1, $result['total_errors']);
        $this->assertSame('Row 2: Missing required fields', $result['errors'][0]);
    }

    public function test_preview_rejects_invalid_true_false_values_the_same_way_as_import(): void
    {
        $service = app(InstitutionAssessmentQuestionPreviewService::class);

        $organization = Organization::create([
            'name' => 'Alpha Hospital',
            'slug' => 'alpha-hospital',
            'type' => 'institution',
            'is_active' => true,
        ]);

        $assessment = new \App\Models\Institution\InstitutionAssessment([
            'organization_id' => $organization->id,
        ]);

        $csv = <<<CSV
question_text,type,points,topic_optional,explanation_optional,choice_1_correct_answer,choice_2,choice_3,choice_4
True false sample,true_false,1,Anatomy,Why,maybe,False,,
CSV;

        $file = UploadedFile::fake()->createWithContent('questions.csv', $csv);

        $result = $service->preview($assessment, $file);

        $this->assertSame(0, $result['total_valid']);
        $this->assertSame(1, $result['total_errors']);
        $this->assertSame(
            "Row 2: True/False questions must use 'true' or 'false' in choice_1_correct_answer",
            $result['errors'][0]
        );
    }
}
