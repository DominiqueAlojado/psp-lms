<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CME/CPD Tracking Enabled
    |--------------------------------------------------------------------------
    |
    | This setting controls whether CME/CPD credit tracking is enabled
    | system-wide. When disabled, no credits will be awarded and CME/CPD
    | features will be hidden from the UI.
    |
    | Set to true to enable CME/CPD tracking for all organizations.
    | Set to false to disable CME/CPD tracking system-wide.
    |
    */

    'enabled' => env('CME_CPD_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Minimum Event Attendance Percentage
    |--------------------------------------------------------------------------
    |
    | The minimum percentage of event duration a user must attend to earn
    | CME/CPD credits. For example, 0.5 means 50% attendance is required.
    |
    */

    'minimum_attendance_percentage' => env('CME_CPD_MIN_ATTENDANCE', 0.5),
];
