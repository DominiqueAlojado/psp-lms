<?php

namespace Tests\Unit;

use App\Models\QuestionBank;
use App\Models\QuestionBankStatistic;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionBankStatisticIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prevents_duplicate_national_statistics_rows_for_the_same_question(): void
    {
        $user = User::factory()->create();

        $question = QuestionBank::create([
            'created_by' => $user->id,
            'owner_type' => 'national',
            'question_type' => 'multiple_choice',
            'question_text' => 'National question',
            'points' => 1,
        ]);

        QuestionBankStatistic::create([
            'question_id' => $question->id,
            'scope' => 'national',
            'institution_id' => null,
        ]);

        $this->expectException(QueryException::class);

        QuestionBankStatistic::create([
            'question_id' => $question->id,
            'scope' => 'national',
            'institution_id' => null,
        ]);
    }

    public function test_update_statistics_reuses_the_existing_national_statistics_row(): void
    {
        $user = User::factory()->create();

        $question = QuestionBank::create([
            'created_by' => $user->id,
            'owner_type' => 'national',
            'question_type' => 'multiple_choice',
            'question_text' => 'National update stats',
            'points' => 1,
        ]);

        $question->updateStatistics(true, null, 'national', null);
        $question->refresh();
        $question->updateStatistics(false, null, 'national', null);

        $this->assertSame(1, QuestionBankStatistic::query()
            ->where('question_id', $question->id)
            ->where('scope', 'national')
            ->whereNull('institution_id')
            ->count());

        $stats = QuestionBankStatistic::query()
            ->where('question_id', $question->id)
            ->where('scope', 'national')
            ->whereNull('institution_id')
            ->firstOrFail();

        $this->assertSame(2, $stats->times_answered);
        $this->assertSame(1, $stats->times_correct);
        $this->assertSame(1, $stats->times_incorrect);
    }
}
