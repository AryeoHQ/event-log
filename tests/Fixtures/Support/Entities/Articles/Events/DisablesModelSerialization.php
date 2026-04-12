<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Articles\Events;

use Support\Events\Log\Attributes\Alias;
use Support\Events\Log\Concerns\SupportsEventLog;
use Support\Events\Log\Contracts\Recordable;
use Tests\Fixtures\Support\Entities\Articles\Article;

#[Alias('article.disables_model_serialization')]
final class DisablesModelSerialization implements Recordable
{
    use SupportsEventLog;

    public readonly Article $entity;

    public function __construct(Article $entity)
    {
        $this->entity = $entity;
    }
}
