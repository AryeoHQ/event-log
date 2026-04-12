<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Illuminate\Database\Eloquent\Model;
use Support\Events\Log\Attributes\Alias;
use Support\Events\Log\Concerns\SupportsEventLog;
use Support\Events\Log\Contracts\Recordable;

#[Alias('test.created')]
final class ValidRecordableEvent implements Recordable
{
    use SupportsEventLog;

    public readonly Model $entity;

    public function __construct(Model $entity)
    {
        $this->entity = $entity;
    }
}
