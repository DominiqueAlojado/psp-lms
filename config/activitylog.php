<?php

return [

    /*
     * If set to false, no activities will be saved to the database.
     */
    'enabled' => env('ACTIVITY_LOG_ENABLED', true),

    /*
     * When the clean-command is executed, all recording activities older than
     * the number of days specified here will be deleted.
     */
    'delete_records_older_than_days' => env('ACTIVITY_LOG_DELETE_OLDER_THAN_DAYS', 365),

    /*
     * If no log name is passed to the activity() helper
     * we use this default log name.
     */
    'default_log_name' => env('ACTIVITY_LOG_DEFAULT_LOG_NAME', 'default'),

    /*
     * You can specify an auth driver here that gets used to determine
     * if a user is logged in or not.
     */
    'default_auth_driver' => null,

    /*
     * When set to true, the subject returns soft deleted models.
     */
    'subject_returns_soft_deleted_models' => false,

    /*
     * The model used to log activities.
     */
    'activity_model' => \Spatie\Activitylog\Models\Activity::class,

    /*
     * You can change the middleware used to load the authenticated user.
     */
    'middleware' => [
        'auth',
    ],

];
