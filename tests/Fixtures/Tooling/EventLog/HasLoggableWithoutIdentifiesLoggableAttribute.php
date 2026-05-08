<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\Provides\HasLoggable;
use Tests\Fixtures\Support\Entities\Articles\Article;

final class HasLoggableWithoutIdentifiesLoggableAttribute implements Recordable
{
    use HasLoggable;

    public readonly Article $article;

    public function __construct() {}
}
