<?php

declare(strict_types=1);

namespace Support\Events\Providers;

use Illuminate\Events\Dispatcher as BaseDispatcher;
use Illuminate\Support\ServiceProvider;
use Support\Events\Dispatcher;

class EventLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend('events', fn (BaseDispatcher $original, $app) => new Dispatcher($original));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../../config/event_log.php' => config_path('event_log.php'),
        ], 'config');

        $this->loadMigrationsFrom([
            __DIR__ . '/../Logs/Migrations',
            __DIR__ . '/../Destinations/Migrations',
            __DIR__ . '/../Deliveries/Migrations',
            __DIR__ . '/../Attempts/Migrations',
        ]);
    }
}
