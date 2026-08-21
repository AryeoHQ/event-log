<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches\Collecting\Provides;

use Illuminate\Support\Collection;
use Support\Events\Log\Envelopes\Envelope;

trait CollectsEnvelopes
{
    /**
     * @var \Illuminate\Support\Collection<array-key, \Support\Events\Log\Envelopes\Envelope>
     */
    public Collection $envelopes {
        get => $this->envelopes ??= collect();
    }

    public function add(Envelope $envelope): static
    {
        $this->envelopes->push($envelope);

        return $this;
    }
}
