<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Tries;

use Attribute;
use Deprecated;

#[Attribute(Attribute::TARGET_CLASS)]
final class Tries
{
    public readonly int $count;

    #[Deprecated('Class will be removed in favor of `#[Tries]` provided by Laravel 13.')]
    public function __construct(int $count)
    {
        $this->count = $count;
    }
}
