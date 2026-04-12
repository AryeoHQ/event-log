<?php

declare(strict_types=1);

namespace Support\Events\Log\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Stringable;

interface Recordable
{
    public Model $entity { get; }

    public Stringable $alias { get; }

    public Stringable $uniqueAlias { get; }
}
