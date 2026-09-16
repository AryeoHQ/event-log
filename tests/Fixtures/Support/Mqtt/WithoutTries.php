<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Mqtt;

use Support\Events\Log\Transports\Contracts\Transport;
use Support\Events\Log\Transports\Dispatches\Dispatches;
use Tests\Fixtures\Support\Mqtt\Collecting\Events\NeedsEnvelopes;
use Tests\Fixtures\Support\Mqtt\Sending\Events\NeedsSent;

#[Dispatches(collecting: NeedsEnvelopes::class, sending: NeedsSent::class)]
interface WithoutTries extends Transport {}
