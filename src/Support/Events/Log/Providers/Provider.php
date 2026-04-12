<?php

declare(strict_types=1);

namespace Support\Events\Log\Providers;

use Illuminate\Events\Dispatcher as BaseDispatcher;
use Illuminate\Support\ServiceProvider;
use Support\Events\Log\Dispatcher;
use Support\Events\Log\Dispatcher\Mixins\DisablesSerializesModels;
use Support\Events\Log\Manager;

class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../../../config/event_log.php', 'event_log');

        $this->app->singleton(Manager::class);

        $this->app->extend('events', fn (BaseDispatcher $original, $app) => new Dispatcher($original));
    }

    public function boot(): void
    {
        BaseDispatcher::mixin(new DisablesSerializesModels);

        $this->publishes([
            __DIR__.'/../../../../../config/event_log.php' => config_path('event_log.php'),
        ], 'config');

        $this->loadMigrationsFrom([
            __DIR__.'/../Logs/Migrations',
            __DIR__.'/../Destinations/Migrations',
            __DIR__.'/../Deliveries/Migrations',
            __DIR__.'/../Attempts/Migrations',
        ]);
    }
}
