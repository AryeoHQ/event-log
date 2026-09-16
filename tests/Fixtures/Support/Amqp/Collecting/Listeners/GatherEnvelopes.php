<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Amqp\Collecting\Listeners;

use Support\Events\Log\Envelopes\Envelope;
use Tests\Fixtures\Support\Amqp\Collecting\Events\NeedsEnvelopes;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;

final class GatherEnvelopes
{
    public function handle(NeedsEnvelopes $event): void
    {
        $event->add(
            Envelope::make(recipient: Recordable::factory()->create())
        );
    }
}
