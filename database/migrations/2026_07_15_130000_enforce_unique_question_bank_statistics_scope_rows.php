<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->deduplicateStatisticsRows();
        $this->dropLegacyIndexes();
        $this->createScopedUniqueIndexes();
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS qbs_national_scope_unique');
        DB::statement('DROP INDEX IF EXISTS qbs_institution_scope_unique');

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS qbs_question_scope_institution_unique ON question_bank_statistics (question_id, scope, institution_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS qbs_scope_institution_index ON question_bank_statistics (scope, institution_id)');
        } elseif ($driver === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS qbs_question_scope_institution_unique ON question_bank_statistics (question_id, scope, institution_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS qbs_scope_institution_index ON question_bank_statistics (scope, institution_id)');
        }
    }

    private function deduplicateStatisticsRows(): void
    {
        $groups = DB::table('question_bank_statistics')
            ->select('question_id', 'scope', 'institution_id', DB::raw('COUNT(*) as duplicate_count'))
            ->groupBy('question_id', 'scope', 'institution_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $rows = DB::table('question_bank_statistics')
                ->where('question_id', $group->question_id)
                ->where('scope', $group->scope)
                ->when($group->institution_id === null, fn ($q) => $q->whereNull('institution_id'))
                ->when($group->institution_id !== null, fn ($q) => $q->where('institution_id', $group->institution_id))
                ->orderByDesc('times_answered')
                ->orderByDesc('times_used_in_exams')
                ->orderByDesc('statistics_updated_at')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();

            $keeper = $rows->first();

            if (! $keeper) {
                continue;
            }

            $aggregate = [
                'times_used_in_exams' => $rows->sum('times_used_in_exams'),
                'times_answered' => $rows->sum('times_answered'),
                'times_correct' => $rows->sum('times_correct'),
                'times_incorrect' => $rows->sum('times_incorrect'),
                'skip_count' => $rows->sum('skip_count'),
                'average_time_seconds' => $this->weightedAverageTimeSeconds($rows),
                'discrimination_index' => $this->latestNonNullValue($rows, 'discrimination_index'),
                'computed_difficulty' => $this->latestNonNullValue($rows, 'computed_difficulty'),
                'last_used_at' => $this->latestNonNullValue($rows, 'last_used_at'),
                'statistics_updated_at' => $this->latestNonNullValue($rows, 'statistics_updated_at'),
                'updated_at' => now(),
            ];

            $aggregate['success_rate'] = $aggregate['times_answered'] > 0
                ? round(($aggregate['times_correct'] / $aggregate['times_answered']) * 100, 2)
                : 0;

            DB::table('question_bank_statistics')
                ->where('id', $keeper->id)
                ->update($aggregate);

            $duplicateIds = $rows->skip(1)->pluck('id')->all();

            if ($duplicateIds !== []) {
                DB::table('question_bank_statistics')
                    ->whereIn('id', $duplicateIds)
                    ->delete();
            }
        }
    }

    private function dropLegacyIndexes(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE question_bank_statistics DROP CONSTRAINT IF EXISTS qbs_question_scope_institution_unique');
            DB::statement('ALTER TABLE question_bank_statistics DROP CONSTRAINT IF EXISTS question_bank_statistics_question_id_scope_institution_id_unique');
            DB::statement('ALTER TABLE question_bank_statistics DROP CONSTRAINT IF EXISTS question_bank_statistics_question_id_scope_institution_id_uniqu');
        }

        DB::statement('DROP INDEX IF EXISTS qbs_question_scope_institution_unique');
        DB::statement('DROP INDEX IF EXISTS qbs_scope_institution_index');
        DB::statement('DROP INDEX IF EXISTS question_bank_statistics_question_id_scope_institution_id_unique');
        DB::statement('DROP INDEX IF EXISTS question_bank_statistics_question_id_scope_institution_id_uniqu');
    }

    private function createScopedUniqueIndexes(): void
    {
        DB::statement("
            CREATE UNIQUE INDEX qbs_national_scope_unique
            ON question_bank_statistics (question_id)
            WHERE scope = 'national' AND institution_id IS NULL
        ");

        DB::statement("
            CREATE UNIQUE INDEX qbs_institution_scope_unique
            ON question_bank_statistics (question_id, institution_id)
            WHERE scope = 'institution' AND institution_id IS NOT NULL
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS qbs_scope_institution_index
            ON question_bank_statistics (scope, institution_id)
        ");
    }

    private function weightedAverageTimeSeconds($rows): ?float
    {
        $weightedTotal = 0.0;
        $weight = 0;

        foreach ($rows as $row) {
            if ($row->average_time_seconds === null || (int) $row->times_answered <= 0) {
                continue;
            }

            $weightedTotal += (float) $row->average_time_seconds * (int) $row->times_answered;
            $weight += (int) $row->times_answered;
        }

        if ($weight === 0) {
            return null;
        }

        return round($weightedTotal / $weight, 2);
    }

    private function latestNonNullValue($rows, string $field): mixed
    {
        foreach ($rows as $row) {
            if ($row->{$field} !== null) {
                return $row->{$field};
            }
        }

        return null;
    }
};
