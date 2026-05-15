<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;
use Tests\Fixtures\Support\Entities\Articles\Article;

final class HasLoggableWithMultipleIdentifiesLoggableAttributes implements Recordable
{
    use HasLoggable;

    #[IdentifiesLoggable]
    public readonly Article $first;

    #[IdentifiesLoggable]
    public readonly Article $second;

    public function __construct() {}
}
