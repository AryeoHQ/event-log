<?php

declare(strict_types=1);

namespace Support\Events\Log\Contracts;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

interface Loggable
{
    /**
     * @return iterable<string, mixed>|JsonSerializable|Jsonable|Arrayable<string, mixed>
     */
    public function toLoggable(): iterable|JsonSerializable|Jsonable|Arrayable;
}
