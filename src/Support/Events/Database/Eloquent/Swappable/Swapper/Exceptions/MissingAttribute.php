<?php

declare(strict_types=1);

namespace Support\Events\Database\Eloquent\Swappable\Swapper\Exceptions;

use RuntimeException;

final class MissingAttribute extends RuntimeException
{
    /**
     * @param  class-string  $model
     * @param  class-string  $attribute
     */
    public function __construct(string $model, string $attribute)
    {
        parent::__construct('['.$model.'] must declare #['.class_basename($attribute).'].');
    }
}
