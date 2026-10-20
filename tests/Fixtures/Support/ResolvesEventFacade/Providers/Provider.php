<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\ResolvesEventFacade\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class Provider extends ServiceProvider
{
    public function register(): void
    {
        Event::getFacadeRoot(); // Accessing the facade caches the dispatcher it resolves.
    }
}
