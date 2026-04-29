<?php

declare(strict_types=1);

namespace Support\Events\Log\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Stringable;
use Support\Events\Log\Logs\Log;

interface Recordable
{
    public Log $log { get; set; }

    public Model&Loggable $loggable { get; }

    public string $loggableProperty { get; }

    public Stringable $alias { get; }

    public Stringable $uniqueAlias { get; }
}
