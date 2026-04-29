<?php

declare(strict_types=1);

namespace Support\Events\Log\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Support\Events\Dispatcher\Mixins\DisablesSerializesModels;
use Support\Events\Log\Dispatcher\Dispatcher;
use Support\Events\Log\Logs\Log;

class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
        $this->registerBindings();
    }

    public function boot(): void
    {
        $this->bootMorphMap();
        $this->bootMixins();
        $this->bootMigrations();
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

    private function bootMorphMap(): void
    {
        Relation::morphMap([
            'event_log' => Log::class,
        ]);
    }

    private function bootMixins(): void
    {
        \Illuminate\Events\Dispatcher::mixin(new DisablesSerializesModels);
    }

    private function bootMigrations(): void
    {
        $this->loadMigrationsFrom([__DIR__.'/../Logs/Migrations']);
    }
}
