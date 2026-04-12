<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Illuminate\Database\Eloquent\Model;
use Support\Events\Log\Concerns\SupportsEventLog;
use Support\Events\Log\Contracts\Recordable;

#[\AllowDynamicProperties]
final class RecordableWithoutAlias implements Recordable
{
    use SupportsEventLog;

    public readonly Model $entity;

    public function __construct(Model $entity)
    {
        $this->entity = $entity;
    }
}
