<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telescope Domain
    |--------------------------------------------------------------------------
    */

    'domain' => env('TELESCOPE_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Telescope Path
    |--------------------------------------------------------------------------
    */

    'path' => env('TELESCOPE_PATH', 'telescope'),

    /*
    |--------------------------------------------------------------------------
    | Telescope Storage Driver
    |--------------------------------------------------------------------------
    */

    'driver' => env('TELESCOPE_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => [
        'web',
        \App\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        \Laravel\Telescope\Http\Middleware\Authorize::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Watchers
    |--------------------------------------------------------------------------
    */

    'watchers' => [
        \Laravel\Telescope\Watchers\CacheWatcher::class => env('TELESCOPE_CACHE_WATCHER', true),
        \Laravel\Telescope\Watchers\CommandWatcher::class => env('TELESCOPE_COMMAND_WATCHER', true),
        \Laravel\Telescope\Watchers\DumpWatcher::class => env('TELESCOPE_DUMP_WATCHER', true),
        \Laravel\Telescope\Watchers\EventWatcher::class => env('TELESCOPE_EVENT_WATCHER', true),
        \Laravel\Telescope\Watchers\ExceptionWatcher::class => env('TELESCOPE_EXCEPTION_WATCHER', true),
        \Laravel\Telescope\Watchers\JobWatcher::class => env('TELESCOPE_JOB_WATCHER', true),
        \Laravel\Telescope\Watchers\LogWatcher::class => env('TELESCOPE_LOG_WATCHER', true),
        \Laravel\Telescope\Watchers\MailWatcher::class => env('TELESCOPE_MAIL_WATCHER', true),
        \Laravel\Telescope\Watchers\ModelWatcher::class => env('TELESCOPE_MODEL_WATCHER', true),
        \Laravel\Telescope\Watchers\NotificationWatcher::class => env('TELESCOPE_NOTIFICATION_WATCHER', true),
        \Laravel\Telescope\Watchers\QueryWatcher::class => env('TELESCOPE_QUERY_WATCHER', true),
        \Laravel\Telescope\Watchers\RedisWatcher::class => env('TELESCOPE_REDIS_WATCHER', true),
        \Laravel\Telescope\Watchers\RequestWatcher::class => env('TELESCOPE_REQUEST_WATCHER', true),
        \Laravel\Telescope\Watchers\ScheduleWatcher::class => env('TELESCOPE_SCHEDULE_WATCHER', true),
        \Laravel\Telescope\Watchers\ViewWatcher::class => env('TELESCOPE_VIEW_WATCHER', true),
        \Laravel\Telescope\Watchers\GateWatcher::class => env('TELESCOPE_GATE_WATCHER', true),
    ],
];
