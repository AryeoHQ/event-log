<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\DisablesModelSerializationThroughInterface\Events;

use Stringable;
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;
use Tests\Fixtures\Support\Entities\NonRecordable\NonRecordable;

#[Alias('non_recordable.disables_serialization_through_interface')]
final class DisablesModelSerializationThroughInterface implements Recordable, Stringable
{
    use HasLoggable;

    #[IdentifiesLoggable]
    public NonRecordable $nonRecordable;

    public function __construct(NonRecordable $nonRecordable)
    {
        $this->nonRecordable = $nonRecordable;
    }

    public function __toString(): string
    {
        return self::class;
    }
}
