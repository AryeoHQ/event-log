<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Articles\Events;

use Tests\Fixtures\Support\Entities\Articles\Article;

final class Retrieved
{

    public readonly Article $entity;

    public function __construct(Article $entity)
    {
        $this->entity = $entity;
    }
}
