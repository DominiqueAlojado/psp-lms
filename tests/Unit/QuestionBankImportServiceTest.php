<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\User;
use App\Services\QuestionBankImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class QuestionBankImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_previews_question_rows(): void
    {
        $service = app(QuestionBankImportService::class);

        $csv = <<<CSV
question_text,type,points,topic,explanation,choice_1,choice_1_correct_answer,choice_2,choice_2_correct_answer
Sample question,multiple_choice,1,Anatomy,Why,A,yes,B,no
CSV;

        $file = UploadedFile::fake()->createWithContent('questions.csv', $csv);

        $result = $service->preview($file);

        $this->assertSame(1, $result['total_valid']);
        $this->assertSame('Sample question', $result['questions'][0]['question_text']);
    }

    public function test_it_imports_question_rows(): void
    {
        $service = app(QuestionBankImportService::class);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $csv = <<<CSV
question_text,type,points,topic,explanation,choice_1,choice_1_correct_answer,choice_2,choice_2_correct_answer
Imported question,multiple_choice,1,Anatomy,Why,A,yes,B,no
CSV;

        $file = UploadedFile::fake()->createWithContent('questions.csv', $csv);

        $result = $service->import($user, $file);

        $this->assertSame(1, $result['successCount']);
        $this->assertDatabaseHas('question_bank', [
            'question_text' => 'Imported question',
            'organization_id' => $organization->id,
        ]);
    }
}
