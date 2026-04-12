<?php

declare(strict_types=1);

namespace Support\Events\Log\Attributes\Exceptions;

use RuntimeException;

final class AliasMissing extends RuntimeException
{
    public static function on(string $class): self
    {
        return new self("The [{$class}] event must have the #[Alias] attribute.");
    }
}
