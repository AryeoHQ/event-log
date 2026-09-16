<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Relayable\Events;

use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\RecordableAfterCommit;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;
use Support\Events\Log\Provides\HasRelays;
use Tests\Fixtures\Support\Amqp\Amqp;
use Tests\Fixtures\Support\Entities\Relayable\Relayable;
use Tests\Fixtures\Support\Mqtt\Mqtt;

#[Alias('relayable.multiplexed')]
final class Multiplexed implements Amqp, Mqtt, RecordableAfterCommit
{
    use HasLoggable;
    use HasRelays;

    #[IdentifiesLoggable]
    public Relayable $relayable;

    public function __construct(Relayable $relayable)
    {
        $this->relayable = $relayable;
    }
}
