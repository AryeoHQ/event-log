<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Illuminate\Support\Collection;
use Support\Events\Log\Envelopes\Envelope;
use Support\Events\Log\Relays\Relay;
use Support\Events\Log\Transports\Dispatches\Collecting\Contracts\NeedsEnvelopes;

final class NeedsEnvelopesWithoutCollectsEnvelopes implements NeedsEnvelopes
{
    public readonly Relay $relay;

    /** @var \Illuminate\Support\Collection<array-key, \Support\Events\Log\Envelopes\Envelope> */
    public Collection $envelopes {
        get => $this->envelopes ??= collect();
    }

    public function __construct(Relay $relay)
    {
        $this->relay = $relay;
    }

    public function add(Envelope $envelope): static
    {
        return $this;
    }
}
