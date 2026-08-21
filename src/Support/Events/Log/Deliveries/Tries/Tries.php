<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Tries;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Tries
{
    public readonly int $count;

    public function __construct(int $count)
    {
        $this->count = $count;
    }
}
