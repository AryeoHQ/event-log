<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches\Exceptions;

use RuntimeException;
use Support\Events\Log\Transports\Dispatches\Dispatches;

final class NotDefined extends RuntimeException
{
    /**
     * @param  class-string<\Support\Events\Log\Transports\Contracts\Transport>  $transport
     */
    public function __construct(string $transport)
    {
        parent::__construct(class_basename($transport).' is missing a #['.class_basename(Dispatches::class).'] attribute.');
    }
}
