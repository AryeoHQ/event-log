<?php

declare(strict_types=1);

namespace Support\Events\Log\Alias;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Alias
{
    public readonly string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
