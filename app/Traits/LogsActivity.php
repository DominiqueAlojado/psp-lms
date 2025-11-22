<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Facades\Activity;

trait LogsActivity
{
    /**
     * Log an activity with the specified log name.
     */
    protected function logActivity(
        Model $subject,
        string $description,
        string $logName,
        array $attributes = [],
        array $oldValues = [],
        ?Model $causer = null
    ): void {
        $activity = activity()
            ->performedOn($subject)
            ->causedBy($causer ?? Auth::user())
            ->useLog($logName);

        if (! empty($attributes) || ! empty($oldValues)) {
            $activity->withProperties([
                'attributes' => $attributes,
                'old' => $oldValues,
            ]);
        }

        $activity->log($description);
    }

    /**
     * Temporarily disable activity logging, execute callback, then re-enable.
     */
    protected function withoutActivityLogging(callable $callback): mixed
    {
        try {
            Activity::disableLogging();
            return $callback();
        } finally {
            Activity::enableLogging();
        }
    }
}
