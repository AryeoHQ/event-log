<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Amqp;

use Support\Events\Log\Deliveries\Tries\Tries;
use Support\Events\Log\Transports\Contracts\Transport;
use Support\Events\Log\Transports\Dispatches\Dispatches;
use Tests\Fixtures\Support\Amqp\Collecting\Events\NeedsEnvelopes;
use Tests\Fixtures\Support\Amqp\Sending\Events\NeedsSent;

#[Dispatches(collecting: NeedsEnvelopes::class, sending: NeedsSent::class)]
#[Tries(3)]
interface Amqp extends Transport {}
