<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Mqtt\Factories\Mixins;

use Closure;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;
use Support\Events\Log\Envelopes\Envelope;
use Support\Events\Log\Logs\Log;
use Support\Events\Log\Relays\Relay;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\Fixtures\Support\Entities\Relayable\Events\Updated;
use Tests\Fixtures\Support\Entities\Relayable\Relayable;
use Tests\Fixtures\Support\Mqtt\Mqtt as Transport;

/** @mixin \Illuminate\Database\Eloquent\Factories\Factory<\Illuminate\Database\Eloquent\Model> */
final class Mqtt
{
    /** @return Closure(class-string<\Support\Events\Log\Transports\Contracts\Transport> $transport=): \Illuminate\Database\Eloquent\Factories\Factory<\Illuminate\Database\Eloquent\Model> */
    public function mqtt(): Closure
    {
        return function (string $transport = Transport::class) {
            return match ($this->modelName()) {
                Log::class => $this->state(fn (): array => [
                    'event' => new Updated(Relayable::factory()->create()),
                ]),
                Relay::class => $this
                    ->for(Log::factory()->mqtt(), 'log')
                    ->state(['transport' => $transport]),
                Delivery::class => $this
                    ->for(Relay::factory()->mqtt($transport), 'relay')
                    ->state(fn (): array => [
                        'envelope' => Envelope::make(recipient: Recordable::factory()->create()),
                    ]),
                DeliveryAttempt::class => $this
                    ->for(Delivery::factory()->mqtt($transport), 'delivery'),
                default => $this,
            };
        };
    }
}
