<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Mqtt\Sending\Listeners;

use Tests\Fixtures\Support\Mqtt\Sending\Events\NeedsSent;

final class Publish
{
    public function handle(NeedsSent $event): void
    {
        $event->result('published');
    }
}
