<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Watchdog;

final class Watchdog
{
    public function bite(): Bite
    {
        return Bite::make();
    }
}
