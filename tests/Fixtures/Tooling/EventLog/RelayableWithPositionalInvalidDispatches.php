<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Support\Events\Log\Transports\Contracts\Transport;
use Support\Events\Log\Transports\Dispatches\Dispatches;

#[Dispatches(CollectsEnvelopesWithoutNeedsEnvelopes::class, RecordsResultWithoutNeedsSent::class)]
interface RelayableWithPositionalInvalidDispatches extends Transport {}
