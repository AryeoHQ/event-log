<?php

declare(strict_types=1);

namespace Support\Events\Log\Contracts;

use Support\Entities\Events\Contracts\ForEntity;

interface RecordableAfterCommit extends ForEntity {}
