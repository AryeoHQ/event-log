<?php

declare(strict_types=1);

namespace Support\Events\Log\Concerns;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Stringable;
use ReflectionClass;
use Support\Events\Log\Attributes\Alias;
use Support\Events\Log\Attributes\Exceptions\AliasMissing;

trait SupportsEventLog
{
    use Dispatchable;
    use SerializesModels;

    public Stringable $alias {
        get => str(
            collect((new ReflectionClass($this))->getAttributes(Alias::class))
                ->first()?->newInstance()->name ?? throw AliasMissing::on(static::class)
        );
    }

    public Stringable $uniqueAlias {
        get => str($this->alias->explode('.')->join(".{$this->entity->id}."));
    }
}
