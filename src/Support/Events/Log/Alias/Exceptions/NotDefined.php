<?php

declare(strict_types=1);

namespace Support\Events\Log\Alias\Exceptions;

use RuntimeException;
use Support\Events\Log\Alias\Alias;

final class NotDefined extends RuntimeException
{
    public static function on(string $class): self
    {
        return new self('The ['.class_basename($class).'] event must have the #['.class_basename(Alias::class).'] attribute.');
    }
}
