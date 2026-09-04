<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Mqtt;

use Support\Events\Log\Deliveries\Tries\Tries;
use Support\Events\Log\Transports\Contracts\Transport;
use Support\Events\Log\Transports\Dispatches\Dispatches;
use Support\Events\Log\Transports\Dispatches\Queues;
use Tests\Fixtures\Support\Mqtt\Collecting\Events\NeedsEnvelopes;
use Tests\Fixtures\Support\Mqtt\Sending\Events\NeedsSent;

#[Dispatches(collecting: NeedsEnvelopes::class, sending: NeedsSent::class)]
#[Queues(collecting: 'mqtt.queues.collecting', sending: 'mqtt.queues.sending')]
#[Tries(3)]
interface Queued extends Transport {}
