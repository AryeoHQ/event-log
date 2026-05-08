<?php

declare(strict_types=1);

namespace Support\Events\Log\IdentifiesLoggable\Exceptions;

use Illuminate\Support\Stringable;
use LogicException;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;

final class NotDefined extends LogicException
{
    private Stringable $template { get => str('[%s] is missing a property annotated with [%s].'); }

    public function __construct(Recordable $event)
    {
        parent::__construct(
            $this->template->replaceArray('%s', [class_basename($event::class), class_basename(IdentifiesLoggable::class)])->toString(),
        );
    }
}
