<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Amqp\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Tests\Fixtures\Support\Amqp\Collecting\Events\NeedsEnvelopes;
use Tests\Fixtures\Support\Amqp\Collecting\Listeners\GatherEnvelopes;
use Tests\Fixtures\Support\Amqp\Sending\Events\NeedsSent;
use Tests\Fixtures\Support\Amqp\Sending\Listeners\Publish;

final class Provider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(NeedsEnvelopes::class, GatherEnvelopes::class);
        Event::listen(NeedsSent::class, Publish::class);
    }
}
