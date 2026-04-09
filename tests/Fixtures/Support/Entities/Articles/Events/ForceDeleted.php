<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Articles\Events;

use Support\Entities\Events\Attributes\Alias;
use Support\Entities\Events\Contracts\ForEntity;
use Support\Entities\Events\Provides\EntityDriven;
use Tests\Fixtures\Support\Entities\Articles\Article;

#[Alias('article.force-deleted')]
final class ForceDeleted implements ForEntity
{
    use EntityDriven;

    public readonly Article $entity;

    public function __construct(Article $entity)
    {
        $this->entity = $entity;
    }
}
