<?php

declare(strict_types=1);

namespace Support\Events\Database\Eloquent\Swappable\Swapper\Exceptions;

use RuntimeException;
use Support\Events\Database\Eloquent\Swappable\Models\Concerns\SupportsSwapping;
use Support\Events\Database\Eloquent\Swappable\Models\Contracts\Swappable;

final class NotSwappable extends RuntimeException
{
    /**
     * @param  class-string  $model
     */
    public function __construct(string $model)
    {
        parent::__construct('['.$model.'] must implement ['.Swappable::class.'] and use ['.SupportsSwapping::class.'].');
    }
}
