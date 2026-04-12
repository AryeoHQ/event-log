<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Stringable;
use Support\Events\Log\Contracts\Recordable;

final class RecordableWithoutSupportsEventLog implements Recordable
{
    public readonly Model $entity;

    public Stringable $alias {
        get => str('test');
    }

    public Stringable $uniqueAlias {
        get => str('test');
    }

    public function __construct(Model $entity)
    {
        $this->entity = $entity;
    }
}
