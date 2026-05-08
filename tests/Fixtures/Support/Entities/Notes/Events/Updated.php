<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Notes\Events;

use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;
use Tests\Fixtures\Support\Entities\Notes\Note;

#[Alias('note.updated')]
final class Updated implements Recordable
{
    use HasLoggable;

    #[IdentifiesLoggable]
    public Note $note;

    public function __construct(Note $note)
    {
        $this->note = $note;
    }
}
