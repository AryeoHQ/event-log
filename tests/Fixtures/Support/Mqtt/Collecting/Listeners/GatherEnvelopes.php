<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Mqtt\Collecting\Listeners;

use Support\Events\Log\Envelopes\Envelope;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\Fixtures\Support\Mqtt\Collecting\Events\NeedsEnvelopes;

final class GatherEnvelopes
{
    public function handle(NeedsEnvelopes $event): void
    {
        $event->add(
            Envelope::make(recipient: Recordable::factory()->create())
        );
    }
}
