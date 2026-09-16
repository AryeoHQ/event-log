<?php

declare(strict_types=1);

namespace Support\Events\Log\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Support\Events\Dispatcher\Mixins\DisablesSerializesModels;
use Support\Events\Log\Deliveries;
use Support\Events\Log\DeliveryAttempts;
use Support\Events\Log\Dispatcher\Dispatcher;
use Support\Events\Log\Logs;
use Support\Events\Log\Relays;

class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
        $this->registerBindings();
    }

    public function boot(): void
    {
        $this->bootMixins();
        $this->bootMigrations();
        $this->bootListeners();
        $this->bootCommands();
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../../../config/event_log.php', 'event_log');
    }

    private function registerBindings(): void
    {
        $this->registerEventDispatcherDecorator();
    }

    private function registerEventDispatcherDecorator(): void
    {
        if (! config('event_log.enabled')) {
            return;
        }

        $this->app->extend('events', fn (\Illuminate\Events\Dispatcher $original) => new Dispatcher($original));
    }

    private function bootMixins(): void
    {
        \Illuminate\Events\Dispatcher::mixin(new DisablesSerializesModels);
    }

    private function bootMigrations(): void
    {
        $this->loadMigrationsFrom([
            __DIR__.'/../Logs/Migrations',
            __DIR__.'/../Relays/Migrations',
            __DIR__.'/../Deliveries/Migrations',
            __DIR__.'/../DeliveryAttempts/Migrations',
        ]);
    }

    private function bootListeners(): void
    {
        Event::listen(Logs\Events\Created::class, Logs\Listeners\InitiateLifecycle::class);
        Event::listen(Relays\Events\Created::class, Relays\Listeners\InitiateLifecycle::class);
        Event::listen(Deliveries\Events\Created::class, Deliveries\Listeners\InitiateLifecycle::class);
        Event::listen(DeliveryAttempts\Events\Created::class, DeliveryAttempts\Listeners\InitiateLifecycle::class);
    }

    private function bootCommands(): void
    {
        $this->commands([
            Logs\Watchdog\Console\Commands\Watchdog::class,
            Relays\Watchdog\Console\Commands\Watchdog::class,
            Deliveries\Watchdog\Console\Commands\Watchdog::class,
            DeliveryAttempts\Watchdog\Console\Commands\Watchdog::class,
        ]);
    }
}
