<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches\Collecting\Contracts;

use Illuminate\Support\Collection;
use Support\Events\Log\Envelopes\Envelope;
use Support\Events\Log\Relays\Relay;

interface NeedsEnvelopes
{
    public Relay $relay { get; }

    /**
     * @var \Illuminate\Support\Collection<array-key, \Support\Events\Log\Envelopes\Envelope>
     */
    public Collection $envelopes { get; }

    public function add(Envelope $envelope): static;
}
