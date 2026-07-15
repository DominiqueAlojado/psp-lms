<?php

namespace App\Console\Commands;

use App\Models\ExamIdlePeriod;
use App\Models\Institution\InstitutionAttempt;
use App\Models\National\NationalAttempt;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class SimulateIdlePeriods extends Command
{
    protected $signature = 'exam:simulate-idle-periods';

    protected $description = 'Simulate idle periods for testing the monitoring system';

    public function handle(): int
    {
        $this->info('🧪 Idle Period Simulator');
        $this->newLine();

        // Ask for exam type
        $type = select(
            label: 'Select exam type',
            options: [
                'institution' => 'Institution Exam',
                'national' => 'In-Service Exam',
            ],
        );

        // Ask for attempt ID
        $attemptId = text(
            label: 'Enter attempt ID',
            required: true,
            validate: fn ($value) => is_numeric($value) ? null : 'Must be a number',
        );

        // Verify attempt exists
        if ($type === 'institution') {
            $attempt = InstitutionAttempt::find($attemptId);
        } else {
            $attempt = NationalAttempt::find($attemptId);
        }

        if (! $attempt) {
            $this->error('❌ Attempt not found!');

            return self::FAILURE;
        }

        $this->info("Found attempt for: {$attempt->user->name}");
        $this->newLine();

        // Confirm
        if (! confirm('Create 3 simulated idle periods?', true)) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        // Generate 3 fake idle periods with different durations
        $idlePeriods = [
            ['duration' => 240], // 4 minutes
            ['duration' => 180], // 3 minutes
            ['duration' => 300], // 5 minutes
        ];

        $this->info('Creating simulated idle periods...');
        $this->newLine();

        $baseTime = now()->subMinutes(20); // Start 20 minutes ago
        $totalIdleTime = 0;

        foreach ($idlePeriods as $index => $period) {
            $startedAt = $baseTime->copy()->addMinutes($index * 6); // Space them out by 6 minutes
            $endedAt = $startedAt->copy()->addSeconds($period['duration']);

            ExamIdlePeriod::create([
                'attempt_type' => $type,
                'attempt_id' => $attemptId,
                'institution_attempt_id' => $type === 'institution' ? (int) $attemptId : null,
                'national_attempt_id' => $type === 'national' ? (int) $attemptId : null,
                'user_id' => $attempt->user_id,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'duration_seconds' => $period['duration'],
            ]);

            $totalIdleTime += $period['duration'];
            $durationFormatted = gmdate('H:i:s', $period['duration']);

            $this->line('  ✓ Period #'.($index + 1).": {$durationFormatted} ({$startedAt->format('h:i A')} - {$endedAt->format('h:i A')})");
        }

        // Update aggregate counters
        $attempt->increment('total_idle_time', $totalIdleTime);
        $attempt->increment('idle_periods_count', 3);

        // Update max if needed
        $maxDuration = max(array_column($idlePeriods, 'duration'));
        if ($maxDuration > $attempt->max_idle_duration) {
            $attempt->update(['max_idle_duration' => $maxDuration]);
        }

        $this->newLine();
        $this->info('✅ Successfully created 3 simulated idle periods!');
        $this->info('💡 Total idle time: '.gmdate('H:i:s', $totalIdleTime));
        $this->info("💡 View them in Live Monitor or check attempt #{$attemptId}");

        return self::SUCCESS;
    }
}
