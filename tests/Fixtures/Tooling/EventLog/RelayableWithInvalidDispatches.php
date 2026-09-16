<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Support\Events\Log\Transports\Contracts\Transport;
use Support\Events\Log\Transports\Dispatches\Dispatches;

#[Dispatches(collecting: CollectsEnvelopesWithoutNeedsEnvelopes::class, sending: RecordsResultWithoutNeedsSent::class)]
interface RelayableWithInvalidDispatches extends Transport {}
