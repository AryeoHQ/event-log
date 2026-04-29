<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Articles\Events;

use Tests\Fixtures\Support\Entities\Articles\Article;

final class Viewed
{
    public Article $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }
}
