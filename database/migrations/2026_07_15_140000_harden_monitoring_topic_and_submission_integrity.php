<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addMonitoringForeignKeys();
        $this->backfillMonitoringAttemptReferences();
        $this->replaceTopicSlugIndexes();
        $this->normalizeSubmissionNumbers();
        $this->addSubmissionSequenceIndex();
    }

    public function down(): void
    {
        $this->dropSubmissionSequenceIndex();
        $this->restoreTopicSlugIndexes();
        $this->dropMonitoringForeignKeys();
    }

    private function addMonitoringForeignKeys(): void
    {
        Schema::table('exam_session_changes', function (Blueprint $table) {
            $table->foreignId('institution_attempt_id')
                ->nullable()
                ->after('attempt_id')
                ->constrained('institution_attempts')
                ->cascadeOnDelete();
            $table->foreignId('national_attempt_id')
                ->nullable()
                ->after('institution_attempt_id')
                ->constrained('national_attempts')
                ->cascadeOnDelete();
            $table->index('institution_attempt_id');
            $table->index('national_attempt_id');
        });

        Schema::table('exam_idle_periods', function (Blueprint $table) {
            $table->foreignId('institution_attempt_id')
                ->nullable()
                ->after('attempt_id')
                ->constrained('institution_attempts')
                ->cascadeOnDelete();
            $table->foreignId('national_attempt_id')
                ->nullable()
                ->after('institution_attempt_id')
                ->constrained('national_attempts')
                ->cascadeOnDelete();
            $table->index('institution_attempt_id');
            $table->index('national_attempt_id');
        });
    }

    private function backfillMonitoringAttemptReferences(): void
    {
        DB::table('exam_session_changes')
            ->where('attempt_type', 'institution')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('institution_attempts')
                    ->whereColumn('institution_attempts.id', 'exam_session_changes.attempt_id');
            })
            ->delete();

        DB::table('exam_session_changes')
            ->whereIn('attempt_type', ['national', 'inservice'])
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('national_attempts')
                    ->whereColumn('national_attempts.id', 'exam_session_changes.attempt_id');
            })
            ->delete();

        DB::table('exam_session_changes')
            ->where('attempt_type', 'institution')
            ->update(['institution_attempt_id' => DB::raw('attempt_id')]);

        DB::table('exam_session_changes')
            ->whereIn('attempt_type', ['national', 'inservice'])
            ->update([
                'attempt_type' => 'national',
                'national_attempt_id' => DB::raw('attempt_id'),
            ]);

        DB::table('exam_idle_periods')
            ->where('attempt_type', 'institution')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('institution_attempts')
                    ->whereColumn('institution_attempts.id', 'exam_idle_periods.attempt_id');
            })
            ->delete();

        DB::table('exam_idle_periods')
            ->whereIn('attempt_type', ['national', 'inservice'])
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('national_attempts')
                    ->whereColumn('national_attempts.id', 'exam_idle_periods.attempt_id');
            })
            ->delete();

        DB::table('exam_idle_periods')
            ->where('attempt_type', 'institution')
            ->update(['institution_attempt_id' => DB::raw('attempt_id')]);

        DB::table('exam_idle_periods')
            ->whereIn('attempt_type', ['national', 'inservice'])
            ->update([
                'attempt_type' => 'national',
                'national_attempt_id' => DB::raw('attempt_id'),
            ]);
    }

    private function replaceTopicSlugIndexes(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropUnique('topics_slug_unique');
        });

        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('CREATE UNIQUE INDEX topics_global_slug_unique ON topics (slug) WHERE is_global = true');
            DB::statement('CREATE UNIQUE INDEX topics_organization_slug_unique ON topics (organization_id, slug) WHERE is_global = false AND organization_id IS NOT NULL');

            return;
        }

        Schema::table('topics', function (Blueprint $table) {
            $table->unique(['organization_id', 'slug'], 'topics_organization_slug_unique');
        });
    }

    private function restoreTopicSlugIndexes(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS topics_global_slug_unique');
            DB::statement('DROP INDEX IF EXISTS topics_organization_slug_unique');
        } else {
            Schema::table('topics', function (Blueprint $table) {
                $table->dropUnique('topics_organization_slug_unique');
            });
        }

        Schema::table('topics', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    private function normalizeSubmissionNumbers(): void
    {
        $duplicateGroups = DB::table('submissions')
            ->select('assignment_id', 'user_id')
            ->whereNull('deleted_at')
            ->groupBy('assignment_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $submissions = DB::table('submissions')
                ->where('assignment_id', $group->assignment_id)
                ->where('user_id', $group->user_id)
                ->whereNull('deleted_at')
                ->orderByRaw('CASE WHEN submitted_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('submitted_at')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id']);

            foreach ($submissions as $index => $submission) {
                DB::table('submissions')
                    ->where('id', $submission->id)
                    ->update(['submission_number' => $index + 1]);
            }
        }
    }

    private function addSubmissionSequenceIndex(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('CREATE UNIQUE INDEX submissions_assignment_user_sequence_unique ON submissions (assignment_id, user_id, submission_number) WHERE deleted_at IS NULL');

            return;
        }

        Schema::table('submissions', function (Blueprint $table) {
            $table->unique(['assignment_id', 'user_id', 'submission_number'], 'submissions_assignment_user_sequence_unique');
        });
    }

    private function dropSubmissionSequenceIndex(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS submissions_assignment_user_sequence_unique');

            return;
        }

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropUnique('submissions_assignment_user_sequence_unique');
        });
    }

    private function dropMonitoringForeignKeys(): void
    {
        Schema::table('exam_session_changes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('institution_attempt_id');
            $table->dropConstrainedForeignId('national_attempt_id');
        });

        Schema::table('exam_idle_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('institution_attempt_id');
            $table->dropConstrainedForeignId('national_attempt_id');
        });
    }
};
