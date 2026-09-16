<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Mqtt\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Tests\Fixtures\Support\Mqtt\Collecting\Events\NeedsEnvelopes;
use Tests\Fixtures\Support\Mqtt\Collecting\Listeners\GatherEnvelopes;
use Tests\Fixtures\Support\Mqtt\Factories\Mixins\Mqtt;
use Tests\Fixtures\Support\Mqtt\Sending\Events\NeedsSent;
use Tests\Fixtures\Support\Mqtt\Sending\Listeners\Publish;

final class Provider extends ServiceProvider
{
    public function boot(): void
    {
        Factory::mixin(new Mqtt);

        Event::listen(NeedsEnvelopes::class, GatherEnvelopes::class);
        Event::listen(NeedsSent::class, Publish::class);
    }
}
