<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Articles\Events;

use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\RecordableAfterCommit;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;
use Tests\Fixtures\Support\Entities\Articles\Article;

#[Alias('article.updated')]
final class Updated implements RecordableAfterCommit
{
    use HasLoggable;

    #[IdentifiesLoggable]
    public Article $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }
}
