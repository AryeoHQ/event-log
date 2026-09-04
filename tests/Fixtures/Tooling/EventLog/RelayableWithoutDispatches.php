<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Support\Events\Log\Transports\Contracts\Transport;

interface RelayableWithoutDispatches extends Transport {}
