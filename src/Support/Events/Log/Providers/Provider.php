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
use Support\Events\Log\Transportables;
use Tooling\EventLog\Composer\ClassMap\Collectors\Transports;

final class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
        $this->registerBindings();
        $this->registerCollectors();
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

    private function registerCollectors(): void
    {
        $this->app->tag([Transports::class], 'tooling.classmap.collectors');
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
            __DIR__.'/../Transportables/Migrations',
        ]);
    }

    private function bootListeners(): void
    {
        Event::listen(data_get(resolve(Logs\Log::using())->dispatchesEvents(), 'created'), Logs\Listeners\InitiateLifecycle::class);
        Event::listen(data_get(resolve(Relays\Relay::using())->dispatchesEvents(), 'created'), Relays\Listeners\InitiateLifecycle::class);
        Event::listen(data_get(resolve(Deliveries\Delivery::using())->dispatchesEvents(), 'created'), Deliveries\Listeners\InitiateLifecycle::class);
        Event::listen(data_get(resolve(DeliveryAttempts\DeliveryAttempt::using())->dispatchesEvents(), 'created'), DeliveryAttempts\Listeners\InitiateLifecycle::class);
    }

    private function bootCommands(): void
    {
        $this->commands([
            Logs\Watchdog\Console\Commands\Watchdog::class,
            Relays\Watchdog\Console\Commands\Watchdog::class,
            Deliveries\Watchdog\Console\Commands\Watchdog::class,
            DeliveryAttempts\Watchdog\Console\Commands\Watchdog::class,
            Transportables\Console\Commands\Synchronize::class,
        ]);
    }
}
