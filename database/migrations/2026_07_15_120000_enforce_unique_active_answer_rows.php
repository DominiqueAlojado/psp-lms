<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->deduplicateActiveAnswers('institution_answers');
        $this->deduplicateActiveAnswers('national_answers');

        $this->dropPlainAttemptQuestionIndex('institution_answers', 'institution_answers_attempt_id_question_id_index');
        $this->dropPlainAttemptQuestionIndex('national_answers', 'national_answers_attempt_id_question_id_index');

        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('CREATE UNIQUE INDEX institution_answers_attempt_question_active_unique ON institution_answers (attempt_id, question_id) WHERE deleted_at IS NULL');
            DB::statement('CREATE UNIQUE INDEX national_answers_attempt_question_active_unique ON national_answers (attempt_id, question_id) WHERE deleted_at IS NULL');

            return;
        }

        Schema::table('institution_answers', function (Blueprint $table) {
            $table->unique(['attempt_id', 'question_id'], 'institution_answers_attempt_question_unique');
        });

        Schema::table('national_answers', function (Blueprint $table) {
            $table->unique(['attempt_id', 'question_id'], 'national_answers_attempt_question_unique');
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS institution_answers_attempt_question_active_unique');
            DB::statement('DROP INDEX IF EXISTS national_answers_attempt_question_active_unique');
        } else {
            Schema::table('institution_answers', function (Blueprint $table) {
                $table->dropUnique('institution_answers_attempt_question_unique');
            });

            Schema::table('national_answers', function (Blueprint $table) {
                $table->dropUnique('national_answers_attempt_question_unique');
            });
        }

        Schema::table('institution_answers', function (Blueprint $table) {
            $table->index(['attempt_id', 'question_id']);
        });

        Schema::table('national_answers', function (Blueprint $table) {
            $table->index(['attempt_id', 'question_id']);
        });
    }

    private function deduplicateActiveAnswers(string $table): void
    {
        $duplicateGroups = DB::table($table)
            ->select('attempt_id', 'question_id', DB::raw('COUNT(*) as duplicate_count'))
            ->whereNull('deleted_at')
            ->groupBy('attempt_id', 'question_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $rows = DB::table($table)
                ->where('attempt_id', $group->attempt_id)
                ->where('question_id', $group->question_id)
                ->whereNull('deleted_at')
                ->orderByRaw('CASE WHEN graded_at IS NOT NULL THEN 1 ELSE 0 END DESC')
                ->orderByRaw('CASE WHEN is_correct IS NOT NULL THEN 1 ELSE 0 END DESC')
                ->orderByDesc('points_earned')
                ->orderByDesc('answer_change_count')
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();

            $keeper = $rows->first();

            if (! $keeper) {
                continue;
            }

            $maxAnswerChangeCount = $rows->max(fn ($row) => (int) ($row->answer_change_count ?? 0));

            DB::table($table)
                ->where('id', $keeper->id)
                ->update([
                    'answer_change_count' => $maxAnswerChangeCount,
                    'updated_at' => now(),
                ]);

            $duplicateIds = $rows
                ->skip(1)
                ->pluck('id')
                ->all();

            if ($duplicateIds === []) {
                continue;
            }

            DB::table($table)
                ->whereIn('id', $duplicateIds)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);
        }
    }

    private function dropPlainAttemptQuestionIndex(string $table, string $indexName): void
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        } catch (\Throwable $exception) {
            // Ignore if the original non-unique index is already absent.
        }
    }
};
