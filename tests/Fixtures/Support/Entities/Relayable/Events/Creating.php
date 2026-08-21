<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Relayable\Events;

use Tests\Fixtures\Support\Entities\Relayable\Relayable;

final class Creating
{
    public Relayable $relayable;

    public function __construct(Relayable $relayable)
    {
        $this->relayable = $relayable;
    }
}
