<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Comments\Events;

use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;
use Tests\Fixtures\Support\Entities\Comments\Comment;

#[Alias('comment.updated')]
final class Updated implements Recordable
{
    use HasLoggable;

    #[IdentifiesLoggable]
    public Comment $comment;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }
}
