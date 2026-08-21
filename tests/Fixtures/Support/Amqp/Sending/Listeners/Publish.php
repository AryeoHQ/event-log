<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Amqp\Sending\Listeners;

use Tests\Fixtures\Support\Amqp\Sending\Events\NeedsSent;

final class Publish
{
    public function handle(NeedsSent $event): void
    {
        $event->result('published');
    }
}
