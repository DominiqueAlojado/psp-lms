<?php

namespace App\Console\Commands;

use App\Models\ExamSessionChange;
use App\Models\Institution\InstitutionAttempt;
use App\Models\National\NationalAttempt;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class SimulateIpChanges extends Command
{
    protected $signature = 'exam:simulate-ip-changes';

    protected $description = 'Simulate IP address changes for testing the monitoring system';

    public function handle(): int
    {
        $this->info('🧪 IP Address Change Simulator');
        $this->newLine();

        // Ask for exam type
        $type = select(
            label: 'Select exam type',
            options: [
                'institution' => 'Institution Exam',
                'inservice' => 'In-Service Exam',
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
        if (! confirm('Create 3 simulated IP changes?', true)) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        // Generate 3 fake IP changes
        $fakeIps = [
            ['from' => '192.168.1.100', 'to' => '192.168.1.105'],
            ['from' => '192.168.1.105', 'to' => '10.0.0.50'],
            ['from' => '10.0.0.50', 'to' => '172.16.0.10'],
        ];

        $this->info('Creating simulated IP changes...');
        $this->newLine();

        $baseTime = now()->subMinutes(15); // Start 15 minutes ago

        foreach ($fakeIps as $index => $ipChange) {
            $detectedAt = $baseTime->copy()->addMinutes($index * 3);

            ExamSessionChange::create([
                'attempt_type' => $type,
                'attempt_id' => $attemptId,
                'user_id' => $attempt->user_id,
                'change_type' => 'ip_address',
                'previous_ip_address' => $ipChange['from'],
                'new_ip_address' => $ipChange['to'],
                'previous_user_agent' => $attempt->user_agent,
                'new_user_agent' => $attempt->user_agent,
                'browser_info' => $attempt->browser_metadata,
                'detected_at' => $detectedAt,
            ]);

            $this->line('  ✓ Change #'.($index + 1).": {$ipChange['from']} → {$ipChange['to']} at ".$detectedAt->format('h:i A'));
        }

        // Update counter (without overwriting - add to existing count)
        $attempt->increment('ip_changes_count', 3);

        $this->newLine();
        $this->info('✅ Successfully created 3 simulated IP changes!');
        $this->info("💡 View them in Live Monitor or check attempt #{$attemptId}");

        return self::SUCCESS;
    }
}
