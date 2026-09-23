<?php

declare(strict_types=1);

namespace Support\Events\Database\Eloquent\Swappable\Swapper\Exceptions;

use RuntimeException;

final class Invalid extends RuntimeException
{
    /**
     * @param  class-string  $actual
     * @param  class-string  $expected
     */
    public function __construct(string $actual, string $expected)
    {
        parent::__construct('['.$actual.'] must extend ['.$expected.'].');
    }
}
