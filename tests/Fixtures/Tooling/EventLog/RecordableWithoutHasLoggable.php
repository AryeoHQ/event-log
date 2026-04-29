<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\Recordable;

#[Alias('test.without_has_recordable')]
final class RecordableWithoutHasLoggable implements Recordable {}
