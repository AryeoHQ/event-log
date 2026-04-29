<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities;

use Illuminate\Database\Eloquent\Model;
use Support\Events\Log\Contracts\Loggable;

class NoMorphMap extends Model implements Loggable
{
    /** @return array<string, mixed> */
    public function toLoggable(): iterable
    {
        return [];
    }
}
