<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Tags\Events;

use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;
use Tests\Fixtures\Support\Entities\Tags\Tag;

#[Alias('tag.disables_serialization')]
final class DisablesModelSerialization implements Recordable
{
    use HasLoggable;

    #[IdentifiesLoggable]
    public Tag $tag;

    public function __construct(Tag $tag)
    {
        $this->tag = $tag;
    }
}
